<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Model\Enrollment;

class PendingEnrollmentsController extends BaseController
{
    /**
     * @Route("/admin/pending-regisitrations", name="pending_registrations")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $Enrollments = Enrollment::where('status', 'pending')->where('payment_method', 'unit')->with([
            'Invoice', 'User', 'User.UserCertifications', 'Session.Course', 'Session' => function ($q) {
                $q->orderBy('date1', 'DESC')->orderBy('start_time1', 'DESC');
            },
        ])->get();

        $data = [
            'Enrollments' => $Enrollments,
            'breadcrumbs' => [
                'Pending Registrations' => $this->get('router')->generate('pending_registrations'),
            ],
        ];

        return $this->render('admin/pending-registrations/index.html.twig', $data);
    }

    /**
     * @Route("/admin/pending-regisitrations/{enrollment_id}/cancel", name="pending_registrations_cancel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function cancelAction(Request $request, $enrollment_id)
    {
        $Enrollment = Enrollment::with('Session', 'Session.Course')->find($enrollment_id);
        $Enrollment->setAttribute('status', 'denied');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'admin_cancelled_enrollment', $request, [
            'enrollment_id' => $enrollment_id
        ]);

        $EmailSender = $this->get('app.email_sender');
        $EmailSender->send('student_pending_enrollment_denied', $Enrollment->User, ['Enrollment' => $Enrollment]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('pending_registrations'));
    }

    /**
     * @Route("/admin/pending-regisitrations/{enrollment_id}/approve", name="pending_registrations_approve")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function approveAction(Request $request, $enrollment_id)
    {
        $Enrollment = Enrollment::with('Session', 'Session.Course')->find($enrollment_id);
        $Enrollment->setAttribute('status', 'registered');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'admin_approved_enrollment', $request, [
            'enrollment_id' => $enrollment_id
        ]);

        $EmailSender = $this->get('app.email_sender');
        $EmailSender->send('student_pending_enrollment_approved', $Enrollment->User, ['Enrollment' => $Enrollment]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('pending_registrations'));
    }
}
