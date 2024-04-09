<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends BaseController
{
    /**
     * @Route("/account", name="my_account")
     * @Security("has_role('ROLE_USER')")
     */
    public function accountAction(Request $request)
    {
        $User = $this->getUser();

        if ($User->isEmployee()) {
            $form = $this->createFormBuilder($User)
                ->add('opid', TextType::class, ['disabled' => true, 'attr' => ['readonly' => true, 'disabled' => true], 'label' => 'OpID'])
                ->add('first_name', TextType::class, ['disabled' => true, 'attr' => ['readonly' => true, 'disabled' => true]])
                ->add('last_name', TextType::class, ['disabled' => true, 'attr' => ['readonly' => true, 'disabled' => true]])
                ->add('department', TextType::class, ['disabled' => true, 'attr' => ['readonly' => true, 'disabled' => true]])
                ->add('title', TextType::class, ['disabled' => true, 'attr' => ['readonly' => true, 'disabled' => true]])
                ->add('email', EmailType::class, ['disabled' => true, 'attr' => ['readonly' => true, 'disabled' => true], 'label' => 'Email Address'])
                ->add('phone', TextType::class, ['label' => 'Phone Number'])
                ->add('employee_id', TextType::class, ['label' => 'Employee ID'])
                ->add('address1', TextType::class, ['label' => 'Address Line 1'])
                ->add('address2', TextType::class, ['label' => 'Address Line 2 (Apt, Suite, etc.)'])
                ->add('city', TextType::class)
                ->add('state', TextType::class)
                ->add('zip', TextType::class, ['label' => 'Postal Code'])
                ->add('update_account', SubmitType::class)
                ->getForm();
        } else {
            $form = $this->createFormBuilder($User)
                ->add('first_name', TextType::class)
                ->add('last_name', TextType::class)
                ->add('email', EmailType::class, ['label' => 'Email Address'])
                ->add('phone', TextType::class, ['label' => 'Phone Number'])
                ->add('address1', TextType::class, ['label' => 'Address Line 1'])
                ->add('address2', TextType::class, ['label' => 'Address Line 2 (Apt, Suite, etc.)'])
                ->add('city', TextType::class)
                ->add('state', TextType::class)
                ->add('zip', TextType::class, ['label' => 'Postal Code'])
                ->add('update_account', SubmitType::class)
                ->getForm();
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logUserAction($this->getUser(), 'account_updated', $request, []);

            $ExistingUser = User::where('email', $User->email)->first();

            if ($ExistingUser && $ExistingUser->id != $User->id) {
                $form->get('email')->addError(new FormError('This email address is already in use'));
            } else {
                $User->setAttribute('email', $form->getData()['email']);
                $User->save();

                $this->addFlash('success', 'Your account info has been updated');

                return $this->redirectToRoute('my_account');
            }
        }

        return $this->render('account/account.html.twig', [
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'My Account' => $this->get('router')->generate('my_account'),
            ],
        ]);
    }

    /**
     * @Route("/account/update-password", name="my_password")
     * @Security("has_role('ROLE_USER')")
     */
    public function passwordAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('new_password', RepeatedType::class, [
                'type'           => PasswordType::class,
                'first_options'  => ['label' => 'New Password'],
                'second_options' => ['label' => 'Verify New Password'],
            ])
            ->add('update_password', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logUserAction($this->getUser(), 'password_updated', $request, []);

            if (count($form->getErrors(true)) === 0) {
                $User = $this->getUser();
                $User->setContainer($this->container);

                $User->changePassword($form->getData()['new_password']);

                $User->save();

                $this->addFlash('success', 'Password updated');

                return $this->redirectToRoute('my_password');
            }
        }

        return $this->render('account/password.html.twig', [
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'My Account'  => $this->get('router')->generate('my_account'),
                'My Password' => $this->get('router')->generate('my_password'),
            ],
        ]);
    }
}
