<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Library\DirectoryService;
use AppBundle\Model\User;
use AppBundle\Model\LoginLog;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginController extends BaseController
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('username', TextType::class, ['label' => 'Email Address or OpID', 'constraints' => [new NotBlank()]])
            ->add('password', PasswordType::class)
            ->add('login', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (User::isLocked($form->getData()['username'])) {
                // If the user is locked, do not attempt to authenticate
                $form->get('username')->addError(new FormError('Too many unsuccessful login attempts. Try again later'));
            } elseif (strpos($form->getData()['username'], '@')) {
                // If the username contains an @ symbol, attempt to authenticate with Email
                $this->authenticateWithEmail($form);
            } else {
                // Otherwise, attempt to authenticate with OpID
                $this->authenticateWithOpId($form);
            }

            // Record this login attempt
            $LoginLog             = new LoginLog();
            $LoginLog->username   = $form->getData()['username'];
            $LoginLog->ip_address = $request->getClientIp();
            $LoginLog->user_agent = $request->headers->get('User-Agent');
            $LoginLog->success    = count($form->getErrors(true)) === 0;
            $LoginLog->success    = false;
            $LoginLog->save();

            // If there are no errors and the user is an admin, redirect to the admin home page
            if (count($form->getErrors(true)) === 0 && $this->getUser()->isAdmin()) {
                return $this->redirectToRoute('upcoming_sessions');
            }

            // If there are no errors, redirect to the user home page
            if (count($form->getErrors(true)) === 0) {
                return $this->redirectToRoute('home');
            }
        }

        return $this->render(
            'login/login.html.twig',
            [
                'form'        => $form->createView(),
                'breadcrumbs' => [
                    'Login' => $this->get('router')->generate('login'),
                ],
            ]
        );
    }

    private function authenticateWithEmail(&$form)
    {
        $User = User::where('email', $form->getData()['username'])->first();

        if (!$User) {
            $form->get('username')->addError(new FormError('Unknown email address'));

            return false;
        }

        if (!password_verify($form->getData()['password'], $User->password)) {
            $form->get('password')->addError(new FormError('Incorrect password'));

            return false;
        }

        $this->authenticateUser($User);

        return true;
    }

    private function authenticateWithOpid(&$form)
    {
        // Get a directory Service object
        $directoryService = new DirectoryService($this->container->getParameter('ldap_url'));

        // Attempt to authenticate with the OpID and password supplied
        if (!$user = $directoryService->authenticate($form->getData()['username'], $form->getData()['password'])) {
            $form->get('username')->addError(new FormError($directoryService->getLastError()));

            return false;
        }

        // Initialize a flag
        $need_to_send_a_welcome_email = false;

        // Attempt to find a local database record with this OpID
        $User = User::where(['opid' => $user['opid']])->first();

        // If no record was found for this user, create a new one
        if (!$User) {
            $User = new User();
            $User->setContainer($this->container);

            // Update the flag to indicate that we will need to send a welcome message
            $need_to_send_a_welcome_email = true;
        }

        // Update the user record
        $User->opid       = strtoupper($user['opid']);
        $User->first_name = $user['first_name'];
        $User->last_name  = $user['last_name'];
        $User->department = $user['department'];
        $User->email      = $user['email'];
        $User->title      = $user['title'];
        $User->Save();

        // Send a welcome email if necessary
        if ($need_to_send_a_welcome_email) {
            $EmailSender = $this->get('app.email_sender');
            $EmailSender->send('user_new_employee', $User);
        }

        // Authenticate as the user
        $this->authenticateUser($User);

        return true;
    }

    private function authenticateUser($User)
    {
        $token = new UsernamePasswordToken($User, null, 'main', $User->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        $this->get('security.token_storage')->setToken(null);

        return $this->redirectToRoute('home');
    }
}
