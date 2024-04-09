<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Model\Certification;
use AppBundle\Model\User;
use AppBundle\Model\UserCertification;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/usercertifications")
 */
class UserCertificationManagerController extends BaseController
{
    public $name_plural   = 'User Certifications';

    public $name_singular = 'User Certification';

    public $deletable     = true;

    public function indexQuery()
    {
        return UserCertification::with('User', 'Certification')->orderBy('user_id');
    }

    public function createQuery()
    {
        return new UserCertification();
    }

    public function editQuery($id)
    {
        return UserCertification::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('user_id', ChoiceType::class, [
                'choices' => User::dropdown(),
            ])
            ->add('certification_id', ChoiceType::class, [
                'choices' => Certification::dropdown(),
            ])
            ->add('issued_at', DateType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('expires_at', DateType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ]);
    }

    public function indexColumns()
    {
        return [
            'User'          => [
                'callback' => function ($Record) {
                    return $Record->User->display_name;
                },
            ],
            'Certification' => [
                'callback' => function ($Record) {
                    return $Record->Certification->name;
                },
            ],
            'Issued'        => ['field' => 'issued_at'],
            'Expires'       => ['field' => 'expires_at'],
        ];
    }

    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * @Route("/create/")
     */
    public function createAction(Request $request)
    {
        return parent::createAction($request);
    }

    /**
     * @Route("/edit/{id}")
     */
    public function editAction(Request $request, $id)
    {
        return parent::editAction($request, $id);
    }

    /**
     * @Route("/delete/{id}")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $UserCertification = UserCertification::find($id);
        $UserCertification->delete();

        return $this->redirect('../');
    }
}
