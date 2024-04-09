<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Enrollment;
use AppBundle\Model\Invoice;
use AppBundle\Model\Session;
use AppBundle\Model\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/enrollments")
 */
class EnrollmentManagerController extends BaseController
{
    public $name_plural   = 'Enrollments';

    public $name_singular = 'Enrollment';

    public function indexQuery()
    {
        return Enrollment::with('User', 'Session', 'Session.Course')->orderBy('id');
    }

    public function createQuery()
    {
        return new Enrollment();
    }

    public function editQuery($id)
    {
        return Enrollment::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('session_id', ChoiceType::class, [
                'choices' => Session::dropdown(),
            ])
            ->add('user_id', ChoiceType::class, [
                'choices' => User::dropdown(),
            ])
            ->add('hash', TextType::class, [
                'disabled' => true,
                'attr'     => ['readonly' => true, 'disabled' => true],
            ])
            ->add('payment_method', ChoiceType::class, [
                'choices' => [
                    'unit'    => 'unit',
                    'card'    => 'card',
                    'invoice' => 'invoice',
                ],
            ])
            ->add('cost_center', TextType::class, [
                'required' => false,
            ])
            ->add('manager_name', TextType::class, [
                'required' => false,
            ])
            ->add('manager_contact', TextareaType::class, [
                'required' => false,
            ])
            ->add('card_number', TextType::class)
            ->add('transid', TextType::class)
            ->add('invoice_id', ChoiceType::class, [
                'required'    => false,
                'placeholder' => 'Invoice',
                'choices'     => Invoice::dropdown('number'),
            ])
            ->add('cost', MoneyType::class, [
                'currency' => false,
            ])
            ->add('cost2', MoneyType::class, [
                'label'    => 'Secondary Cost',
                'currency' => false,
            ])
            ->add('book_id', IntegerType::class)
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'pending'        => 'pending',
                    'registered'     => 'registered',
                    'cancel_pending' => 'cancel_pending',
                    'cancelled'      => 'cancelled',
                    'denied'         => 'denied',
                ],
            ])
            ->add('grade', ChoiceType::class, [
                'choices' => [
                    'No Grade' => null,
                    'Pass' => 'pass',
                    'Fail' => 'fail',
                ],
            ])
            ->add('attendance', ChoiceType::class, [
                'choices' => [
                    'No Attendance' => null,
                    'Show'   => 'show',
                    'No Show' => 'noshow',
                ],
            ])
            ->add('cancellation_reason', ChoiceType::class, [
                'placeholder' => '(Cancellation Reason)',
                'required'    => false,
                'choices'     => array_flip(Session::$cancellation_reasons),
            ])
            ->add('note_admin', TextareaType::class, [
                'required' => false,
            ])
            ->add('note_user', TextareaType::class, [
                'required' => false,
            ]);
    }

    public function indexColumns()
    {
        return [
            'User'     => [
                'callback' => function ($Record) {
                    return $Record->User->display_name;
                },
            ],
            'Grade'    => ['field' => 'grade'],
            'Schedule' => [
                'callback' => function ($Record) {
                    return $Record->Session->date1 . ' ' . $Record->Session->start_time1;
                },
            ],
            'Course'   => [
                'callback' => function ($Record) {
                    return $Record->Session->Course->name;
                },
            ],
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
}
