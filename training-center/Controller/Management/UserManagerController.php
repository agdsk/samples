<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/users")
 */
class UserManagerController extends BaseController
{
    public $name_plural = 'Users';

    public $name_singular = 'User';

    public function indexQuery()
    {
        return User::orderBy('last_name', 'ASC');
    }

    public function createQuery()
    {
        return new User();
    }

    public function editQuery($id)
    {
        return User::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('first_name', TextType::class)
            ->add('last_name', TextType::class)
            ->add('opid', TextType::class)
            ->add('employee_id', TextType::class)
            ->add('department', TextType::class)
            ->add('title', TextType::class)
            ->add('email', EmailType::class)
            ->add('role', ChoiceType::class, [
                'choices' => ['User' => 'User', 'Instructor' => 'Instructor', 'Admin' => 'Admin'],
            ])
            ->add('phone', TextType::class)
            ->add('address1', TextType::class)
            ->add('address2', TextType::class)
            ->add('state', TextType::class)
            ->add('zip', TextType::class)
            ->add('country', ChoiceType::class, [
                'choices' => ['US' => 'US', 'CA' => 'CA'],
            ]);
    }

    public function indexColumns()
    {
        return [
            'OpID'       => ['field' => 'opid'],
            'Last Name'  => ['field' => 'last_name'],
            'First Name' => ['field' => 'first_name'],
            'Department' => ['field' => 'department'],
            'Title'      => ['field' => 'title'],
            'Role'       => ['field' => 'role'],
            'Email'      => ['field' => 'email'],
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

    protected function preSave(Request $request, $Record)
    {
        $data = $request->request->all();

        if (array_key_exists('form', $data)) {
            if (array_key_exists('email', $data['form'])) {
                $Record->setAttribute('email', $data['form']['email']);
            }
        }

        return $Record;
    }
}
