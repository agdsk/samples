<?php

namespace AppBundle\Controller;

use AppBundle\Model\InstructorAssignment;
use AppBundle\Model\User;
use AppBundle\Model\EmailLog;
use AppBundle\Model\UserCertification;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\Enrollment;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

use Illuminate\Database\Capsule\Manager as Capsule;

class UserBrowserController extends BaseController
{
    /**
     * @Route("/admin/users", name="user-browser")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('search', TextType::class)
            ->getForm();

        $form->handleRequest($request);

        $data['Users'] = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData()['search'];

            $this->logUserAction($this->getUser(), 'admin_user_search', $request, [
                'query' => $search
            ]);

            $search = explode(' ', $search);

            $Users = User::orderBy('last_name')
                ->limit(1000);

            foreach ($search as $search_word) {
                $Users = $Users->orWhere('first_name', 'LIKE', '%' . $search_word . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $search_word . '%')
                    ->orWhere('email', 'LIKE', '%' . $search_word . '%')
                    ->orWhere('opid', 'LIKE', '%' . $search_word . '%');
            }

            $Users = $Users->get();

            $data['Users'] = $Users;
        }

        $data['form'] = $form->createView();

        $data['breadcrumbs'] = [
            'User Search' => $this->get('router')->generate('user-browser'),
        ];

        return $this->render('admin/user-browser/index.html.twig', $data);
    }

    /**
     * @Route("/admin/users/{id}", name="user-browser-show")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function showUserAction(Request $request, $id)
    {
        $User = User::with('UserCertifications', 'UserCertifications.Certification')->where('id', $id)->first();

        $InstructorAssignments = InstructorAssignment::join('sessions', 'sessions.id', '=', 'instructor_assignment.session_id')
            ->where('user_id', $id)
            ->with('Session', 'Session.Course')
            ->where('user_id', $id)
            ->get();

        $Enrollments = Enrollment::join('sessions', 'sessions.id', '=', 'enrollments.session_id')
            ->orderBy('sessions.date1', 'DESC')
            ->with('Session', 'Session.Course')
            ->where('user_id', $id)
            ->get(['enrollments.id', 'session_id', 'status', 'attendance', 'grade']);

        $UserCertifications = UserCertification::where('user_id', $id)->with('Certification')->orderBy('issued_at', 'DESC')->get();

        $EmailLogs = EmailLog::where('user_id', $id)->where('success', true)->orderBy('created_at', 'DESC')->get();

        return $this->render('admin/user-browser/show.html.twig', [
            'User'                  => $User,
            'InstructorAssignments' => $InstructorAssignments,
            'Enrollments'           => $Enrollments,
            'UserCertifications'    => $UserCertifications,
            'EmailLogs'             => $EmailLogs,
            'breadcrumbs'           => [
                'User Search' => $this->get('router')->generate('user-browser'),
                'User'        => '',
            ],
        ]);
    }
}


