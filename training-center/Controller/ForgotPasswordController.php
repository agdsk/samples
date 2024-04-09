<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Exception;
use AppBundle\Model\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForgotPasswordController extends BaseController
{
    /**
     * @Route("/forgot-password", name="forgot")
     */
    public function forgotAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class)
            ->add('send_password', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $email = $form->getData()['email'];

            $User = User::where('email', $email)->get()->first();

            if (!$User) {
                $form->get('email')->addError(new FormError('That email address was not found'));
            } else {
                try {
                    $User->regenerateLoginToken();
                    $User->save();


                    $this->logUserAction($User, 'sent_password_reset_email', $request, []);

                    $EmailSender = $this->get('app.email_sender');
                    $EmailSender->send('user_forgot_password', $User);

                    return $this->render('forgot-password/request-sent.html.twig');
                } catch (Exception $e) {
                    $this->addFlash('error', 'An error occurred, please try again later');

                    $this->get('logger')->error('Could not send password reset message: ' . $e->getMessage());
                }
            }
        }

        return $this->render('forgot-password/request.html.twig', [
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'Forgot Password' => $this->get('router')->generate('forgot'),
            ],
        ]);
    }

    /**
     * @Route("/reset-password/{token}", name="reset")
     */
    public function resetAction(Request $request, $token)
    {
        $User = User::where('token', $token)->get()->first();

        if (!$User) {
            $this->addFlash('error', 'Unfortunately that link is not valid. Please request your password again.');

            return $this->redirectToRoute('forgot');
        }

        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type'           => PasswordType::class,
                'first_options'  => ['label' => 'Password', 'constraints' => [new NotBlank()]],
                'second_options' => ['label' => 'Repeat Password', 'constraints' => [new NotBlank()]],
            ])
            ->add('submit_password', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $User->setContainer($this->container);
            $User->changePassword($form->getData()['password']);
            $User->Save();

            $this->logUserAction($User, 'reset_password_from_email', $request, []);

            $this->addFlash('success', 'Your password has been changed');

            return $this->redirectToRoute('login');
        }

        $data = [
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'Password Reset' => $this->get('router')->generate('forgot'),
            ],
        ];

        return $this->render('forgot-password/reset_password.html.twig', $data);
    }
}
