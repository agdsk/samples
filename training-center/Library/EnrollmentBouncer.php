<?php

namespace AppBundle\Library;

use AppBundle\Model\Enrollment;

use Illuminate\Database\Capsule\Manager as Capsule;

class EnrollmentBouncer
{
    protected $error;

    public function check($User, $Session)
    {
        if ($this->checkExistingEnrollmentInSameCourse($User, $Session) === false) {
            $this->setError('User is already registered for a session in this course');

            return false;
        }

        if ($this->checkIfSessionIsFull($Session)) {
            $this->setError('This session is full');

            return false;
        }

        if ($Session->online_registration == false) {
            $this->setError('This session is not accepting registrations');

            return false;
        }

        if ($Session->public == false) {
            $this->setError('This session is not accepting registrations');

            return false;
        }

        if ($Session->isPast()) {
            $this->setError('This session is in the past');

            return false;
        }

        return true;
    }

    private function setError($message)
    {
        $this->error = $message;
    }

    public function getError()
    {
        return $this->error;
    }

    private function checkExistingEnrollmentInSameCourse($User, $Session)
    {
        $count = Enrollment::join('sessions', 'sessions.id', '=', 'enrollments.session_id')
            ->join('courses', 'courses.id', '=', 'sessions.course_id')
            ->where('user_id', $User->id)
            ->whereIn('status', ['pending', 'registered'])
            ->where('course_id', $Session->course_id)
            ->where('sessions.date1', '>=', Capsule::raw('CURDATE()'))
            ->count();

        if ($count > 0) {
            return false;
        }

        return true;
    }

    private function checkIfSessionIsFull($Session)
    {
        $current_students = Capsule::table('enrollments')->select('session_id', Capsule::raw('COUNT(*) as count'))->where('session_id', $Session->id)->whereIn('status', ['pending', 'registered'])->pluck('count')[0];

        if ($current_students < $Session->student_max) {
            return false;
        }

        return true;
    }
}