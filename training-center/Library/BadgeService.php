<?php

namespace AppBundle\Library;

use Illuminate\Database\Capsule\Manager as Capsule;

class BadgeService
{
    public function countPendingRegistrations()
    {
        return Capsule::table('enrollments')->select(Capsule::raw('COUNT(*) as count'))->where('status', 'pending')->first()->count;
    }

    public function countPendingCancellations()
    {
        return Capsule::table('enrollments')->select(Capsule::raw('COUNT(*) as count'))->where('status', 'cancel_pending')->first()->count;
    }

    public function countAssignedClasses($id)
    {
        return Capsule::table('instructor_assignment')->join('sessions', 'sessions.id', '=', 'instructor_assignment.session_id')->select(Capsule::raw('COUNT(*) as count'))->where('user_id', $id)->where('sessions.date1', '>=', Capsule::raw('NOW()'))->first()->count;
    }
}
