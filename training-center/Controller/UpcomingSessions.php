<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\Session;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\HttpFoundation\Request;

class UpcomingSessions extends BaseController
{
    /**
     * @Route("/admin/upcoming-sessions", name="upcoming_sessions")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $Sessions = Session::orderBy('date1', 'ASC')
            ->with('Course')
            ->where('date1', '>=', Capsule::raw('curdate() AND ADDDATE(NOW(), +60)'))
            ->orWhere('date2', '>=', Capsule::raw('curdate() AND ADDDATE(NOW(), +60)'))
            ->get();

        $zero_filled = array_fill_keys($Sessions->pluck('id')->toArray(), 0);

        $counts['enrollment']  = Capsule::table('enrollments')->select('session_id', Capsule::raw('COUNT(*) as count'))->whereIn('status', ['pending', 'registered'])->whereIn('session_id', $Sessions->pluck('id'))->groupBy('session_id')->pluck('count', 'session_id');
        $counts['instructors'] = Capsule::table('instructor_assignment')->select('session_id', Capsule::raw('COUNT(*) as count'))->whereIn('session_id', $Sessions->pluck('id'))->groupBy('session_id')->pluck('count', 'session_id');

        $counts['enrollment'] += $zero_filled;
        $counts['instructors'] += $zero_filled;

        $data = [
            'Sessions'    => $Sessions,
            'counts'      => $counts,
            'breadcrumbs' => [
                'Dashboard' => $this->get('router')->generate('upcoming_sessions'),
            ],
        ];

        return $this->render('admin/upcoming-sessions/index.html.twig', $data);
    }
}
