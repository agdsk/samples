<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\Enrollment;
use Symfony\Component\HttpFoundation\Request;

class PendingCancellationsController extends BaseController
{
    /**
     * @Route("/admin/pending-cancellations", name="pending_cancellations")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $Enrollments = Enrollment::where('status', 'cancel_pending')->with([
            'Invoice', 'User', 'Session.Course', 'Session' => function ($q) {
                $q->orderBy('date1', 'DESC')->orderBy('start_time1', 'DESC');
            },
        ])->get();

        $data = [
            'Enrollments' => $Enrollments,
            'breadcrumbs' => [
                'Pending Cancellations' => $this->get('router')->generate('pending_cancellations'),
            ],
        ];

        return $this->render('admin/pending-cancellations/index.html.twig', $data);
    }

    /**
     * @Route("/admin/pending-cancellations/{enrollment_id}/cancel", name="pending_cancellations_cancel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function cancelAction(Request $request, $enrollment_id)
    {
        $Enrollment = Enrollment::find($enrollment_id);
        $Enrollment->setAttribute('status', 'cancelled');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'admin_approved_cancellation', $request, [
            'enrollment_id' => $enrollment_id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('pending_cancellations'));
    }
}
