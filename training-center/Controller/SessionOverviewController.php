<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\Enrollment;
use AppBundle\Model\InstructorAssignment;
use AppBundle\Model\Session;
use AppBundle\Model\User;
use AppBundle\Model\UserCertification;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SessionOverviewController extends BaseController

{
    /**
     * @Route("/admin/session/{session_id}", name="admin_session_overview")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function sessionOverviewAction(Request $request, $session_id)
    {
        $Session               = Session::with('Course', 'Location', 'Course.Program')->where('id', $session_id)->first();
        $CertificationRequired = $Session->Course->CertificationRequired;

        $Enrollments           = Enrollment::join('users', 'users.id', '=', 'enrollments.user_id')->with('User')->where('session_id', $Session->id)->whereIn('status', ['pending', 'registered'])->orderBy('enrollments.status', 'DESC')->orderBy('users.last_name')->get();
        $InstructorAssignments = InstructorAssignment::join('users', 'users.id', '=', 'instructor_assignment.user_id')->with('User')->where('session_id', $session_id)->orderBy('users.last_name')->get();

        if ($CertificationRequired) {
            $UserCertifications = UserCertification::where('certification_id', $CertificationRequired->id)->get();
            $UserCertifications = $UserCertifications->filter(function($UserCertification) {
                return !$UserCertification->isExpired();
            });

            $AvailableInstructors = User::whereIn('id', $UserCertifications->pluck('user_id'))->whereNotIn('id', $InstructorAssignments->pluck('user_id'))->orderBy('last_name')->get();
        } else {
            $AvailableInstructors = [];
        }

        $data = [
            'Session'               => $Session,
            'Enrollments'           => $Enrollments,
            'AvailableInstructors'  => $AvailableInstructors,
            'InstructorAssignments' => $InstructorAssignments,
            'cancellation_reasons'  => Session::$cancellation_reasons,
            'breadcrumbs'           => [
                'Sessions'         => $this->get('router')->generate('upcoming_sessions'),
                'Session Overview' => $this->get('router')->generate('admin_session_overview', ['session_id' => $session_id]),
            ],
        ];

        return $this->render('admin/session-overview/index.html.twig', $data);
    }

    /**
     * @Route("/admin/session/print/{session_id}", name="admin_print_roster")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function rosterPrintAction(Request $request, $session_id)
    {
        $Session = Session::with('Course', 'Course.Program', 'Enrollments', 'Enrollments.User')->where('id', $session_id)->first();

        $data = [
            'Session' => $Session,
        ];

        return $this->render('admin/session-overview/roster.html.twig', $data);
    }

    /**
     * @Route("/admin/session/export/{session_id}", name="admin_export_roster")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function rosterExportAction(Request $request, $session_id)
    {
        $this->logUserAction($this->getUser(), 'roster_exported', $request, [
            'session_id' => $session_id
        ]);

        $Session = Session::with('Course', 'Course.Program')->where('id', $session_id)->first();
        $this->CertificationReceived = $Session->Course->CertificationReceived;

        $Enrollments           = Enrollment::join('users', 'users.id', '=', 'enrollments.user_id')->with(['User', 'User.UserCertifications' => function ($q) {
            $q->where('certification_id', $this->CertificationReceived->id)->orderBy('expires_at', 'DESC')->first();
        }])->where('session_id', $Session->id)->whereIn('status', ['pending', 'registered'])->orderBy('enrollments.status', 'DESC')->orderBy('users.last_name')->get();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($Session) {
            $fp = fopen('php://output', 'w');

            fputcsv($fp, ['Enrollment ID', 'OpID', 'Status', 'Last Name', 'First Name', 'Email', 'Phone', 'Department', 'Title', 'Cost Center', 'Primary Cost', 'Secondary Cost', 'Cert Expiration', 'Employee ID', 'Pro License / Cert #', 'Initials']);

            foreach ($Session->Enrollments as $Enrollment) {
                $UserCertifications = $Enrollment->User->UserCertifications;
                $UserCertifications = $UserCertifications->filter(function($UserCertification) {
                    return $UserCertification->certification_id == $this->CertificationReceived->id;
                });
                if(count($UserCertifications) > 0) {
                    $cert_expiration = $UserCertifications[0]->expires_at;
                } else {
                    $cert_expiration = '';
                }

                if($Enrollment->status !== 'cancelled') {
                    fputcsv(
                        $fp, [
                            $Enrollment->id,
                            $Enrollment->User->opid,
                            ucwords($Enrollment->status),
                            $Enrollment->User->last_name,
                            $Enrollment->User->first_name,
                            $Enrollment->User->email,
                            $Enrollment->User->phone,
                            $Enrollment->User->department,
                            $Enrollment->User->title,
                            $Enrollment->cost_center,
                            $Enrollment->cost,
                            $Enrollment->cost2,
                            $cert_expiration,
                            $Enrollment->User->employee_id,
                            '',
                            '',
                        ]
                    );
                }
            }

            fclose($fp);
        });

        $filename = strtolower($Session->Course->name);
        $filename = str_replace(' ', '-', $filename);
        $filename .= '-' . $Session->date1;
        $filename .= '.csv';

        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $d);

        $response->prepare($request);

        $response->send();

        return $response;
    }

    /**
     * @Route("/admin/instructor/add", name="admin_add_instructor")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function instructorAddAction(Request $request)
    {
        $User    = User::where('id', $request->request->get('user_id'))->first();
        $Session = Session::where('id', $request->request->get('session_id'))->first();

        $InstructorAssignment = new InstructorAssignment();
        $InstructorAssignment->User()->associate($User);
        $InstructorAssignment->Session()->associate($Session);

        $InstructorAssignment->save();

        $this->logUserAction($this->getUser(), 'instructor_added', $request, [
            'instructor' => $User->id,
            'session_id' => $Session->id
        ]);

        if($User->role == 'User') {
            $User->role = 'Instructor';
            $User->save();
        }

        $EmailSender = $this->get('app.email_sender');
        $EmailSender->send('instructor_assignment_added', $User, ['Session' => $Session]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/instructor/remove", name="admin_remove_instructor")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function instructorRemoveAction(Request $request)
    {
        $user_id    = $request->request->get('user_id');
        $session_id = $request->request->get('session_id');
        $User       = User::where('id', $user_id)->first();
        $Session    = Session::where('id', $session_id)->first();

        $InstructorAssignment = InstructorAssignment::where('user_id', $user_id)->where('session_id', $session_id)->first();

        $InstructorAssignment->delete();

        $this->logUserAction($this->getUser(), 'instructor_removed', $request, [
            'instructor' => $User->id,
            'session_id' => $Session->id
        ]);

        $EmailSender = $this->get('app.email_sender');
        $EmailSender->send('instructor_assignment_removed', $User, ['Session' => $Session]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    private function getEnrollment(Request $request)
    {
        $user_id    = $request->request->get('user_id');
        $session_id = $request->request->get('session_id');

        return Enrollment::where('user_id', $user_id)->where('session_id', $session_id)->first();
    }

    private function getInstructorAssignment(Request $request)
    {
        $user_id    = $request->request->get('user_id');
        $session_id = $request->request->get('session_id');

        return InstructorAssignment::where('user_id', $user_id)->where('session_id', $session_id)->first();
    }

    /**
     * @Route("/admin/student/grade/pass", name="admin_student_grade_pass")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentGradePassAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('grade', 'pass');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'student_grade_passed', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        if ($Enrollment->Session->Course->CertificationReceived) {
            $DateIssued  = new Carbon($Enrollment->Session->date2 ? $Enrollment->Session->date2 : $Enrollment->Session->date1);
            $DateExpires = clone $DateIssued;
            $DateExpires->addMonths($Enrollment->Session->Course->CertificationReceived->length * 12);
            $DateExpires = $DateExpires->day($DateExpires->daysInMonth);

            $UserCertification = new UserCertification();
            $UserCertification->User()->associate($Enrollment->User);
            $UserCertification->Certification()->associate($Enrollment->Session->Course->CertificationReceived);
            $UserCertification->Session()->associate($Enrollment->Session);
            $UserCertification->issued_at  = $DateIssued;
            $UserCertification->expires_at = $DateExpires;

            $UserCertification->Save();
        }

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/grade/fail", name="admin_student_grade_fail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentGradeFailAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('grade', 'fail');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'student_grade_failed', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        $UserCertification = UserCertification::where('user_id', $Enrollment->user_id)->where('session_id', $Enrollment->Session->id)->first();

        if ($UserCertification) {
            $UserCertification->delete();
        }

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/grade/undo", name="admin_student_grade_undo")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentGradeUndoAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('grade', null);
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'student_grade_reset', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        $UserCertification = UserCertification::where('user_id', $Enrollment->user_id)->where('session_id', $Enrollment->Session->id)->first();

        if ($UserCertification) {
            $UserCertification->delete();
        }

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/attendance/pass", name="admin_student_attendance_pass")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentAttendanceShowAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('attendance', 'show');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'student_attendance_show', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/attendance/fail", name="admin_student_attendance_fail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentAttendanceNoshowAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('attendance', 'noshow');
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'student_attendance_noshow', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/attendance/undo", name="admin_student_attendance_undo")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentAttendanceUndoAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('attendance', null);
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'student_attendance_reset', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/instructor/attendance/pass", name="admin_instructor_attendance_pass")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function instructorAttendanceShowAction(Request $request)
    {
        $InstructorAssignment = $this->getInstructorAssignment($request);

        $InstructorAssignment->setAttribute('attendance', 'show');
        $InstructorAssignment->save();

        $this->logUserAction($this->getUser(), 'instructor_attendance_show', $request, [
            'instructor_assignment' => $InstructorAssignment->id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/instructor/attendance/fail", name="admin_instructor_attendance_fail")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function instructorAttendanceNoshowAction(Request $request)
    {
        $InstructorAssignment = $this->getInstructorAssignment($request);

        $InstructorAssignment->setAttribute('attendance', 'noshow');
        $InstructorAssignment->save();

        $this->logUserAction($this->getUser(), 'instructor_attendance_noshow', $request, [
            'instructor_assignment' => $InstructorAssignment->id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/instructor/attendance/undo", name="admin_instructor_attendance_undo")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function instructorAttendanceUndoAction(Request $request)
    {
        $InstructorAssignment = $this->getInstructorAssignment($request);

        $InstructorAssignment->setAttribute('attendance', null);
        $InstructorAssignment->save();

        $this->logUserAction($this->getUser(), 'instructor_attendance_reset', $request, [
            'instructor_assignment' => $InstructorAssignment->id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/cancel", name="admin_student_cancel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentCancelAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('status', 'cancel_pending');
        $Enrollment->setAttribute('cancellation_reason', $request->request->get('cancellation_reason'));
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'admin_student_cancel', $request, [
            'enrollment_id' => $Enrollment->id
        ]);

        $EmailSender = $this->get('app.email_sender');
        $EmailSender->send('student_enrollment_cancelled_admin', $Enrollment->User, ['Session' => $Enrollment->Session]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/set-book", name="admin_student_set_book")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentSetBookAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);

        $Enrollment->setAttribute('book_id', $request->request->get('book_id'));
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'set_book', $request, [
            'enrollment_id' => $Enrollment->id,
            'book_id' => $request->request->get('book_id')
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/student/return-book", name="admin_student_return_book")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function studentReturnBookAction(Request $request)
    {
        $Enrollment = $this->getEnrollment($request);
        $book_id = $Enrollment->book_id;

        $Enrollment->setAttribute('book_id', null);
        $Enrollment->save();

        $this->logUserAction($this->getUser(), 'return_book', $request, [
            'enrollment_id' => $Enrollment->id,
            'book_id' => $book_id
        ]);

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }

    /**
     * @Route("/admin/email", name="admin_session_email")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function sessionEmailAction(Request $request)
    {
        $message_body = $request->request->get('message');
        $session_id   = $request->request->get('session_id');

        $Session = Session::with('Course', 'Course.Program', 'Enrollments', 'Enrollments.User')->where('id', $session_id)->first();

        $this->logUserAction($this->getUser(), 'session_bulk_email', $request, [
            'session_id' => $session_id,
            'message_body' => $message_body
        ]);

        foreach ($Session->Enrollments as $Enrollment) {
            if ($Enrollment->status == 'pending' || $Enrollment->status == 'registered') {
                $EmailSender = $this->get('app.email_sender');
                $EmailSender->send('student_new_notification_bulk', $Enrollment->User, ['Session' => $Session, 'message_body' => strip_tags($message_body)]);
            }
        }

        $InstructorAssignments = InstructorAssignment::join('users', 'users.id', '=', 'instructor_assignment.user_id')->with('User')->where('session_id', $session_id)->orderBy('users.last_name')->get();

        foreach ($InstructorAssignments as $InstructorAssignment) {
            $EmailSender = $this->get('app.email_sender');
            $EmailSender->send('student_new_notification_bulk', $InstructorAssignment->User, ['Session' => $Session, 'message_body' => strip_tags($message_body)]);
        }

        $this->addFlash('success', 'Your email has been sent.');

        return $this->ajaxFriendlyRedirect($request, $this->generateUrl('admin_session_overview', ['session_id' => $request->request->get('session_id')]));
    }
}
