<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Certification;
use AppBundle\Model\Course;
use AppBundle\Model\Program;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/courses")
 */
class CourseManagerController extends BaseController
{
    public $name_plural   = 'Courses';

    public $name_singular = 'Course';

    public function indexQuery()
    {
        return Course::with('Program')->orderBy('name', 'ASC');
    }

    public function createQuery()
    {
        return new Course();
    }

    public function editQuery($id)
    {
        return Course::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('program_id', ChoiceType::class, [
                'choices' => Program::dropdown(),
            ])
            ->add('certification_received_id', ChoiceType::class, [
                'required'    => false,
                'placeholder' => '(No Certification Received)',
                'label'       => 'Certification Received',
                'choices'     => Certification::dropdown(),
            ])
            ->add('certification_required_id', ChoiceType::class, [
                'required'    => false,
                'placeholder' => '(No Certification Required)',
                'label'       => 'Certification Required',
                'choices'     => Certification::dropdown(),
            ])
            ->add('default_instructor_max', IntegerType::class)
            ->add('default_student_max', IntegerType::class)
            ->add('days', ChoiceType::class, [
                'choices' => [
                    '1 day course' => 1,
                    '2 day course' => 2,
                ],
            ])
            ->add('default_start_time1', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('default_end_time1', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('default_start_time2', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('default_end_time2', TimeType::class, [
                'input'  => 'string',
                'widget' => 'single_text',
            ])
            ->add('cost_public', MoneyType::class, [
                'currency' => false,
            ])
            ->add('cost_employee', MoneyType::class, [
                'currency' => false,
            ])
            ->add('public', ChoiceType::class, [
                'choices' => [
                    'Do not display course'          => 0,
                    'Display course for enrollments' => 1,
                ],
            ])
            ->add('overview', TextareaType::class, [
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('instruction_prerequisites', TextareaType::class, [
                'required' => false,
            ])
            ->add('instruction_before_class', TextareaType::class, [
                'required' => false,
            ])
            ->add('instruction_bring_to_class', TextareaType::class, [
                'required' => false,
            ])
            ->add('and_or_materials', ChoiceType::class, [
                'choices' => [
                    'All Course Materials are required'    => 'and',
                    'Only one Course Material is required' => 'or',
                ],
            ])
            ->add('rentable', ChoiceType::class, [
                'choices' => [
                    'Course Materials are not rentable' => '0',
                    'Course Materials are rentable'     => '1',
                ],
            ]);
    }

    public function indexColumns()
    {
        return [
            'Name'    => ['field' => 'name'],
            'Program' => [
                'callback' => function ($Record) {
                    return $Record->Program->name;
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
