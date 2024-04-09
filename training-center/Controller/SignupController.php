<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

class SignupController extends BaseController
{
    /**
     * @Route("/signup", name="signup")
     */
    public function signupAction(Request $request)
    {
        $User = new User();
        $User->setContainer($this->container);

        $form = $this->createFormBuilder($User)
            ->add('email', EmailType::class, ['label' => 'Email Address', 'constraints' => [new NotBlank()]])
            ->add('password', RepeatedType::class, [
                'type'           => PasswordType::class,
                'first_options'  => ['label' => 'Password', 'constraints' => [new NotBlank()]],
                'second_options' => ['label' => 'Repeat Password', 'constraints' => [new NotBlank()]],
            ])
            ->add('first_name', TextType::class)
            ->add('last_name', TextType::class)
            ->add('phone', TextType::class, ['label' => 'Phone Number'])
            ->add('create_account', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['email'];
            $email_domain = substr($email, strpos($email, "@") + 1);

            if(in_array($email_domain, User::$blacklisted_domain_names)) {
                $this->addFlash('error', 'If you are a Florida Hospital employee, you must login using your OpID.');
                return $this->redirectToRoute('login');
            } elseif (User::where('email', $User->email)->first()) {
                $form->get('email')->addError(new FormError('This email address is already in use'));
            } else {
                $User->save();

                $this->logUserAction($User, 'signup', $request, [
                    'user_id' => $User->id
                ]);

                $User->regenerateLoginToken();

                $EmailSender = $this->get('app.email_sender');
                $EmailSender->send('user_new_public', $User);

                $this->addFlash('success', 'Signup successful, please login');

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('signup/signup.html.twig', [
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'Signup' => $this->get('router')->generate('signup'),
            ],
        ]);
    }
}
