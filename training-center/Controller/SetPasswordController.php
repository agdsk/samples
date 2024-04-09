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

class SetPasswordController extends BaseController
{
    /**
     * @Route("/set-password/{token}", name="set_password")
     */
    public function setAction(Request $request, $token)
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

            $this->logUserAction($this->getUser(), 'user_set_password', $request, [
                'user_id' => $User->id
            ]);

            $this->addFlash('success', 'Your password has been set');

            return $this->redirectToRoute('login');
        }

        $data = [
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'Password Reset' => $this->get('router')->generate('forgot'),
            ],
        ];

        return $this->render('set-password/set_password.html.twig', $data);
    }
}
