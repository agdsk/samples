<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Course;
use AppBundle\Model\Program;
use Symfony\Component\HttpFoundation\Request;

class CoursesController extends BaseController
{
    /**
     * @Route("/programs/{program_slug}", name="courses")
     */
    public function indexAction(Request $request, $program_slug)
    {
        $Program = Program::where('slug', $program_slug)->first();

        if (!$Program) {
            throw $this->createNotFoundException('Program not found');
        }

        $data = [
            'Program'     => $Program,
            'Courses'     => Course::where('program_id', $Program->id)->where('public', 1)->get(),
            'breadcrumbs' => [
                'Programs'     => $this->get('router')->generate('programs'),
                $Program->name => $this->get('router')->generate('courses', ['program_slug' => $program_slug]),
            ],
        ];

        return $this->render('courses/index.html.twig', $data);
    }

    /**
     * @Route("/programs/{program_slug}/{course_slug}", name="course_detail")
     */
    public function showAction(Request $request, $program_slug, $course_slug)
    {
        $Program = Program::where('slug', $program_slug)->first();

        if (!$Program) {
            throw $this->createNotFoundException('Program not found');
        }

        $Course = Course::where('slug', $course_slug)->with('Materials', 'UpcomingPublicSessions')->first();

        if (!$Course->public) {
            throw $this->createNotFoundException('Course not found');
        }

        if ($Course->program_id != $Program->id) {
            throw $this->createNotFoundException('Course not found');
        }

        $data = [
            'Program'     => $Program,
            'Course'      => $Course,
            'breadcrumbs' => [
                'Programs'     => $this->get('router')->generate('programs'),
                $Program->name => $this->get('router')->generate('courses', ['program_slug' => $program_slug]),
                $Course->name  => $this->get('router')->generate('course_detail', ['program_slug' => $program_slug, 'course_slug' => $Course->slug]),
            ],
        ];

        return $this->render('courses/show.html.twig', $data);
    }
}
