<?php

namespace AppBundle\Command;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Carbon\Carbon;

use AppBundle\Model\User;
use AppBundle\Model\UserCertification;
use AppBundle\Model\InstructorAssignment;
use AppBundle\Model\Session;
use AppBundle\Model\Enrollment;

class PrplNotificationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('prpl:notifications')->setDescription('Send out timed (non-transactional) emails/notifications');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->root_dir = $this->getApplication()->getKernel()->getRootDir();
        $this->output   = $output;

        $EmailSender = $this->getContainer()->get('app.email_sender');

        $today  = Carbon::now();
        $days3  = Carbon::now()->addDays(3);
        $days30 = Carbon::now()->addDays(30);
        $days60 = Carbon::now()->addDays(60);
        $days90 = Carbon::now()->addDays(90);

        $Certifications_today = UserCertification::with('User', 'Certification')->where('expires_at', $today->toDateString())->get();
        $Certifications_30    = UserCertification::with('User', 'Certification')->where('expires_at', $days30->toDateString())->get();
        $Certifications_60    = UserCertification::with('User', 'Certification')->where('expires_at', $days60->toDateString())->get();
        $Certifications_90    = UserCertification::with('User', 'Certification')->where('expires_at', $days90->toDateString())->get();

        $InstructorAssignmentsToday = InstructorAssignment::join('sessions', 'sessions.id', '=', 'instructor_assignment.session_id')->where('sessions.date1', '=', $today->toDateString())->with('Session.Course')->get();
        $InstructorAssignmentsSoon  = InstructorAssignment::join('sessions', 'sessions.id', '=', 'instructor_assignment.session_id')->where('sessions.date1', '=', $days3->toDateString())->with('Session.Course')->get();

        $EnrollmentsToday = Enrollment::join('sessions', 'sessions.id', '=', 'enrollments.session_id')->where('sessions.date1', '=', $today->toDateString())->with('Session.Course')->get();
        $EnrollmentsSoon  = Enrollment::join('sessions', 'sessions.id', '=', 'enrollments.session_id')->where('sessions.date1', '=', $days3->toDateString())->with('Session.Course')->get();

        foreach ($Certifications_today as $UserCertification) {
            $EmailSender->send('student_certification_reminder_0', $UserCertification->User, ['Certification' => $UserCertification->Certification]);
        }

        foreach ($Certifications_30 as $UserCertification) {
            $EmailSender->send('student_certification_reminder_30', $UserCertification->User, ['Certification' => $UserCertification->Certification]);
        }

        foreach ($Certifications_60 as $UserCertification) {
            $EmailSender->send('student_certification_reminder_60', $UserCertification->User, ['Certification' => $UserCertification->Certification]);
        }

        foreach ($Certifications_90 as $UserCertification) {
            $EmailSender->send('student_certification_reminder_90', $UserCertification->User, ['Certification' => $UserCertification->Certification]);
        }

        foreach ($InstructorAssignmentsToday as $InstructorAssignment) {
            $EmailSender->send('instructor_assignment_reminder_0', $InstructorAssignment->User, ['Session' => $InstructorAssignment->Session]);
        }

        foreach ($InstructorAssignmentsSoon as $InstructorAssignment) {
            $EmailSender->send('instructor_assignment_reminder_3', $InstructorAssignment->User, ['Session' => $InstructorAssignment->Session]);
        }

        foreach ($EnrollmentsToday as $Enrollment) {
            $EmailSender->send('student_session_reminder_0', $Enrollment->User, ['Session' => $Enrollment->Session]);
        }

        foreach ($EnrollmentsSoon as $Enrollment) {
            $EmailSender->send('student_session_reminder_3', $Enrollment->User, ['Session' => $Enrollment->Session]);
        }
    }
}
