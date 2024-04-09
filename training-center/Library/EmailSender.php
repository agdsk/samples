<?php

namespace AppBundle\Library;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Swift_Message;
use Carbon\Carbon;

use AppBundle\Model\Enrollment;
use AppBundle\Model\Course;
use AppBundle\Model\Certification;
use AppBundle\Model\EmailLog;

use Symfony\Component\Config\Definition\Exception\Exception;

const HAS_ERROR = true;

class EmailSender {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function send($email_type, $User, array $parameters = null)
    {
		$parameters['User'] = $User;

        if(!is_a($User, 'AppBundle\Model\User')) {
            throw new Exception('Invalid user');
        }

        switch ($email_type) {
        case 'user_new_public':
            $subject = 'Welcome to your FHTC Account, ' . $parameters['User']->first_name;
            break;
        case 'user_new_employee':
            $subject = 'Welcome to your FHTC Account, ' . $parameters['User']->first_name;
            break;
        case 'user_new_bulk':
            $subject = 'Your New FHTC Account Information';
            break;
        case 'user_forgot_password':
            $subject = 'Reset your Password';
            break;
        case 'student_new_enrollment_pending':
            $subject = 'Your Registration Request for ' . $parameters['Enrollment']->Session->Course->name . ' Has Been Received';
            break;
        case 'student_new_enrollment_registered':
            $subject = 'You\'ve Registered for ' . $parameters['Enrollment']->Session->Course->name . ' [' . $parameters['Enrollment']->id . '] at FHTC';
            break;
        case 'student_new_enrollment_registered_credit_card':
            $subject = 'You\'re Enrolled in ' . $parameters['Enrollment']->Session->Course->name;
            break;
        case 'student_new_enrollment_registered_bulk':
            $subject = 'You\'ve been Registered for ' . $parameters['Enrollment']->Session->Course->name . ' at the FHTC';
            break;
        case 'student_new_notification_bulk':
            $subject = 'Admin Update: FHTC ' . $parameters['Session']->Course->name;
            break;
        case 'student_pending_enrollment_approved':
            $subject = 'Your Registration has been Approved for ' . $parameters['Enrollment']->Session->Course->name;
            break;
        case 'student_pending_enrollment_denied':
            $subject = 'FHTC Update: Response to Your Registration Request';
            break;
        case 'student_enrollment_cancelled_student':
            $subject = 'You Cancelled your Enrollment in ' . $parameters['Enrollment']->Session->Course->name;
            break;
        case 'student_enrollment_cancelled_admin':
            $subject = 'An Administrator Cancelled your Enrollment in ' . $parameters['Session']->Course->name;
            break;
        case 'instructor_assignment_added':
            if($parameters['Session']->date2 == null) {
                $subject = 'You\'ve been Assigned to Instruct ' . $parameters['Session']->Course->name . ' on ' . Carbon::parse($parameters['Session']->date1)->toFormattedDateString() . '';
            } else {
                $subject = 'You\'ve been Assigned to Instruct ' . $parameters['Session']->Course->name . ' on ' . Carbon::parse($parameters['Session']->date1)->toFormattedDateString() . ' & ' . Carbon::parse($parameters['Session']->date2)->toFormattedDateString();
            }
            break;
        case 'instructor_assignment_removed':
            if($parameters['Session']->date2 == null) {
                $subject = 'You\'ve been Removed from Instructing ' . $parameters['Session']->Course->name . ' on ' . Carbon::parse($parameters['Session']->date1)->toFormattedDateString() . '';
            } else {
                $subject = 'You\'ve been Removed from Instructing ' . $parameters['Session']->Course->name . ' on ' . Carbon::parse($parameters['Session']->date1)->toFormattedDateString() . ' & ' . Carbon::parse($parameters['Session']->date2)->toFormattedDateString();
            }
            break;
        case 'instructor_assignment_reminder_3':
            $subject = 'Your Assigned Course is in 3 Days';
            break;
        case 'instructor_assignment_reminder_0':
            $date_time = new \DateTime($parameters['Session']->start_time1);
            $subject = 'REMINDER: Your Assigned Course is today at ' . $date_time->format('g:i A');
            break;
        case 'student_certification_reminder_90':
            $subject = 'Your ' . $parameters['Certification']->name . ' certification expires in 90 days';
            break;
        case 'student_certification_reminder_60':
            $subject = 'Your ' . $parameters['Certification']->name . ' certification expires in 60 days';
            break;
        case 'student_certification_reminder_30':
            $subject = 'Your ' . $parameters['Certification']->name . ' certification expires in 30 days';
            break;
        case 'student_certification_reminder_0':
            $subject = 'Your ' . $parameters['Certification']->name . ' certification has expired';
            break;
        case 'student_session_reminder_0':
            $date_time = new \DateTime($parameters['Session']->start_time1);
            $subject = 'Your Course Begins at ' . $date_time->format('g:i A') . ' Today';
            break;
        case 'student_session_reminder_3':
            $subject = 'REMINDER: Your Course Begins in 3 Days';
            break;
        default:
            $this->log($User, null, $email_type, HAS_ERROR, 'Email type does not exist: ' . $email_type);
            throw new Exception('Email type does not exist: ' . $email_type);
            break;
        }

        try {
            $this->email($subject, $User, $parameters, $email_type);
            $this->log($User, $subject, $email_type);
        } catch (Exception $e) {
            $this->log($User, $subject, $email_type, HAS_ERROR, $e->getMessage());
        }
    }

    private function log($User, $subject = null, $key, $has_error = false, $notes = null)
    {
        $EmailLog = new EmailLog();
        $EmailLog->User()->associate($User);
        $EmailLog->email = $User->email;
        $EmailLog->key = $key;
        $EmailLog->success = !$has_error;
        $EmailLog->subject = $subject;

        if($has_error) {
            $EmailLog->notes = $notes;
        }

        $EmailLog->save();
    }

    private function email($subject, $User, $parameters, $key)
    {
        if(!file_exists($this->container->get('kernel')->getRootDir() . '/../src/AppBundle/Resources/views/email/' . $key . '.html.twig')) {
            throw new Exception('Twig file (' . $key . '.html.twig) does not exist');
        }

        if($User->email == null) {
            throw new Exception('User email is empty');
        }
        
        $message = new Swift_Message();
        $message->setSubject($subject);
        $message->setFrom('noreply@floridahospitaltrainingcenter.com');
        $message->setTo($User->email);
        $message->addPart($this->container->get('templating')->render('email/' . $key . '.html.twig', $parameters), 'text/html');

        $this->container->get('swiftmailer.mailer.default')->send($message);

    }
}
