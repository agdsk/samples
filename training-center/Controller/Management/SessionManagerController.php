<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Course;
use AppBundle\Model\Location;
use AppBundle\Model\Session;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/sessions")
 */
class SessionManagerController extends BaseController
{
    public $name_plural   = 'Sessions';

    public $name_singular = 'Session';

    public function indexQuery()
    {
        return Session::with('Course')->orderBy('date1', 'DESC');
    }

    public function createQuery()
    {
        return new Session();
    }

    public function editQuery($id)
    {
        return Session::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('course_id', ChoiceType::class, [
                'choices' => Course::dropdown(),
            ])
            ->add('location_id', ChoiceType::class, [
                'label'   => 'Location',
                'choices' => Location::dropdown(),
            ])
            ->add('date1', DateType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('start_time1', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('end_time1', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('date2', DateType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('start_time2', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('end_time2', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('instructor_max', IntegerType::class)
            ->add('student_max', IntegerType::class)
            ->add('online_registration', ChoiceType::class, [
                'choices' => [
                    'Do not allow online registration' => 0,
                    'Allow online registration'        => 1,
                ],
            ])
            ->add('public', ChoiceType::class, [
                'choices' => [
                    'Do not display session'          => 0,
                    'Display session for enrollments' => 1,
                ],
            ]);
    }

    public function indexColumns()
    {
        return [
            'Course' => [
                'callback' => function ($Record) {
                    return $Record->Course->name;
                },
            ],
            'Date 1' => ['field' => 'date1'],
            'Time 1' => ['field' => 'start_time1'],
            'Date 2' => ['field' => 'date2'],
            'Time 2' => ['field' => 'start_time2'],
            'Roster' => ['course_roster' => ''],
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
