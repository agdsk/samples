<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\Course;
use AppBundle\Model\Enrollment;
use AppBundle\Model\InstructorAssignment;
use AppBundle\Model\Program;
use AppBundle\Model\Session;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Illuminate\Database\Capsule\Manager as Capsule;

class ClassesController extends BaseController
{
    /**
     * @Route("/programs/{program_slug}/{course_slug}/classes", name="classes")
     */
    public function indexAction(Request $request, $program_slug, $course_slug)
    {
        $Program = Program::where('slug', $program_slug)->first();

        if (!$Program) {
            throw $this->createNotFoundException('Program not found');
        }

        $Course = Course::with('UpcomingPublicSessions', 'UpcomingPublicSessions.Location')->where('slug', $course_slug)->get()->first();

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
                'Classes'      => $this->get('router')->generate('classes', ['program_slug' => $program_slug, 'course_slug' => $Course->slug]),
            ],
        ];

        return $this->render('classes/index.html.twig', $data);
    }

    /**
     * @Route("/programs/{program_slug}/{course_slug}/classes/enroll/{session_id}", name="enroll")
     * @Security("has_role('ROLE_USER')")
     */
    public function enroll(Request $request, $program_slug, $course_slug, $session_id)
    {
        if ($this->getUser()->isEmployee()) {
            return $this->redirectToRoute('enroll_with_bill_to_unit', ['program_slug' => $program_slug, 'course_slug' => $course_slug, 'session_id' => $session_id]);
        } else {
            return $this->redirectToRoute('enroll_with_credit_card', ['program_slug' => $program_slug, 'course_slug' => $course_slug, 'session_id' => $session_id]);
        }
    }

    /**
     * @Route("/programs/{program_slug}/{course_slug}/classes/{session_id}/enroll/credit/", name="enroll_with_credit_card")
     * @Security("has_role('ROLE_USER')")
     */
    public function enrollWithCreditCardAction(Request $request, $program_slug, $course_slug, $session_id)
    {
        $Program = Program::where('slug', $program_slug)->get()->first();

        if (!$Program) {
            throw $this->createNotFoundException('Program not found');
        }

        $Course = Course::where('slug', $course_slug)->get()->first();

        $Session = Session::find($session_id);

        $EnrollmentBouncer = $this->get('app.enrollment_bouncer');

        if (!$EnrollmentBouncer->check($this->getUser(), $Session)) {
            $this->addFlash('error', $EnrollmentBouncer->getError());

            return $this->redirectToRoute('course_detail', ['program_slug' => $Program->slug, 'course_slug' => $Course->slug]);
        }

        $TrustCommerce = $this->get('app.trustcommerce');
        $token         = $TrustCommerce->generateToken();

        $amount = $this->getUser()->isEmployee() ? $Session->Course->cost_employee : $Session->Course->cost_public;

        $data = [
            'token'       => $token,
            'ticket'      => $Session->id,
            'amount'      => $amount,
            'Program'     => $Program,
            'Course'      => $Course,
            'Session'     => $Session,
            'breadcrumbs' => [
                'Programs'                                 => $this->get('router')->generate('programs'),
                $Program->name                             => $this->get('router')->generate('courses', ['program_slug' => $program_slug]),
                $Course->name                              => $this->get('router')->generate('course_detail', ['program_slug' => $program_slug, 'course_slug' => $Course->slug]),
                date('l, F j', strtotime($Session->date1)) => $this->get('router')->generate('enroll', ['program_slug' => $program_slug, 'course_slug' => $Course->slug, 'session_id' => $Session->id]),
            ],
        ];

        return $this->render('classes/enroll_credit_card.html.twig', $data);
    }

    /**
     * @Route("/verify", name="enrollment_verification")
     * @Security("has_role('ROLE_USER')")
     */
    public function enrollWithCreditCardReturnAction(Request $request)
    {
        if (!$request->get('ticket')) {
            die('No ticket');
        }

        if ($request->get('status') != 'approved' && $request->get('status') != 'decline') {
            die('Unknown status');
        }

        $data = [
            'transid'     => $request->get('transid'),
            'ticket'      => $request->get('ticket'),
            'status'      => $request->get('status'),
            'declinetype' => $request->get('declinetype'),
            'amount'      => $request->get('amount'),
            'address1'    => $request->get('address1'),
            'address2'    => $request->get('address2'),
            'city'        => $request->get('city'),
            'state'       => $request->get('state'),
            'zip'         => $request->get('zip'),
            'phone'       => $request->get('phone'),
            'cc'          => $request->get('cc'),
            'comments'    => $request->get('customfield1'),
        ];

        $Session = Session::find($data['ticket']);
        $Course  = $Session->Course;
        $Program = $Course->Program;

        $data['Program'] = $Program;
        $data['Course']  = $Course;
        $data['Session'] = $Session;

        $data['breadcrumbs'] = [
            'Programs'                                 => $this->get('router')->generate('programs'),
            $Program->name                             => $this->get('router')->generate('courses', ['program_slug' => $Program->slug]),
            $Course->name                              => $this->get('router')->generate('course_detail', ['program_slug' => $Program->slug, 'course_slug' => $Course->slug]),
            date('l, F j', strtotime($Session->date1)) => $this->get('router')->generate('enroll', ['program_slug' => $Program->slug, 'course_slug' => $Course->slug, 'session_id' => $Session->id]),
        ];

        if ($data['status'] == 'decline') {
            $data['declinereason'] = '';

            $decline_reasons = [
                'decline'     => 'Insufficient available funds on the credit card',
                'avs'         => 'Address does not match the billing address on file at the bank',
                'cvv'         => 'Incorrect verification number provided',
                'call'        => 'Manual authorization by phone required',
                'expiredcard' => 'Card is expired',
                'carderror'   => 'Invalid card number',
            ];

            if (array_key_exists($data['declinetype'], $decline_reasons)) {
                $data['declinereason'] = $decline_reasons[$data['declinetype']];
            }

            return $this->render('classes/enroll_credit_card_declined.html.twig', $data);
        }

        if ($data['status'] == 'approved') {
            /*
             * @TODO Verify payment
             *
             * Right now, we're just blindly trusting that the user's payment went through successfully. We tried to
             * use the TrustCommerce API to look up the transaction ID once the user returned, but we discovered that
             * the API takes several seconds to become up to date. Immediately after a user's payment is successful, the
             * API will return 0 query results for that transaction ID. If we wait a couple seconds, however, we will
             * be able to fetch the result. The documentation acknowledges this behavior and says that it's generally
             * unreliable. Until we come up with a solution, we wont' be able to make sure payments post correctly.
             * The client can still verify the transaction IDs against TrustCommerce manually (or in bulk reporting)
             * and rectify any challenges they have with that data.
             *
             * $TrustCommerce = $this->get('app.trustcommerce');
             * $TrustCommerce->verify($data['transid']);
             */

            $Enrollment = new Enrollment();
            $Enrollment->Session()->associate($Session);
            $Enrollment->User()->associate($this->getUser());
            $Enrollment->cost           = $this->getUser()->isEmployee() ? $Session->Course->cost_employee : $Session->Course->cost_public;
            $Enrollment->transid        = $data['transid'];
            $Enrollment->payment_method = 'card';
            $Enrollment->status         = 'registered';
            $Enrollment->card_number    = $data['cc'];

            if ($data['comments'] != '') {
                $Enrollment->note_user = $data['comments'];
            }

            $Enrollment->save();

            $this->logUserAction($this->getUser(), 'enrolled_with_credit_card', $request, [
                'transid' => $data['transid'],
                'enrollment_id' => $Enrollment->id
            ]);

            $EmailSender = $this->get('app.email_sender');
            $EmailSender->send('student_new_enrollment_registered_credit_card', $this->getUser(), ['Enrollment' => $Enrollment]);

            if ($data['address1'] != '') {
                $User           = $this->getUser();
                $User->address1 = $data['address1'];
                $User->save();
            }

            if ($data['address2'] != '') {
                $User           = $this->getUser();
                $User->address2 = $data['address2'];
                $User->save();
            }

            if ($data['city'] != '') {
                $User       = $this->getUser();
                $User->city = $data['city'];
                $User->save();
            }

            if ($data['state'] != '') {
                $User        = $this->getUser();
                $User->state = $data['state'];
                $User->save();
            }

            if ($data['zip'] != '') {
                $User      = $this->getUser();
                $User->zip = $data['zip'];
                $User->save();
            }

            if ($data['phone'] != '') {
                $User        = $this->getUser();
                $User->phone = $data['phone'];
                $User->save();
            }

            return $this->redirectToRoute('enrollment_confirmation', ['hash' => $Enrollment->hash]);
        }
    }

    /**
     * @Route("/programs/{program_slug}/{course_slug}/classes/{session_id}/enroll/unit/", name="enroll_with_bill_to_unit")
     * @Security("has_role('ROLE_USER')")
     */
    public function enrollWithBillToUnitAction(Request $request, $program_slug, $course_slug, $session_id)
    {
        $Program = Program::where('slug', $program_slug)->get()->first();

        if (!$Program) {
            throw $this->createNotFoundException('Program not found');
        }

        $Course = Course::where('slug', $course_slug)->get()->first();

        if ($Course->program_id != $Program->id) {
            throw $this->createNotFoundException('Course not found');
        }

        $Session = Session::find($session_id);

        if (!$Session) {
            throw $this->createNotFoundException('Class not found');
        }

        $EnrollmentBouncer = $this->get('app.enrollment_bouncer');

        if (!$EnrollmentBouncer->check($this->getUser(), $Session)) {
            $this->addFlash('error', $EnrollmentBouncer->getError());

            return $this->redirectToRoute('course_detail', ['program_slug' => $Program->slug, 'course_slug' => $Course->slug]);
        }

        $form = $this->createFormBuilder()
            ->add('cost_center', TextType::class, [
                'required' => true,
                'constraints' => [new NotBlank()],
            ])
            ->add('phone_number', TextType::class, [
                'data'        => $this->getUser()->phone,
                'required'    => true,
                'constraints' => [new NotBlank()],
            ])
            ->add('employee_id', TextType::class, [
                'data'     => $this->getUser()->employee_id,
                'required' => false,
            ])
            ->add('manager_name', TextType::class, [
                'label'    => 'Approving Manager\'s Name (Optional)',
                'required' => false,
            ])
            ->add('manager_contact', TextareaType::class, [
                'label'    => 'Manager Contact Information (Optional)',
                'required' => false,
            ])
            ->add('note_user', TextareaType::class, [
                'label'    => 'Notes (Optional)',
                'required' => false,
            ])
            ->add('enroll', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getData()['phone_number'] != '') {
                $User              = $this->getUser();
                $User->phone       = $form->getData()['phone_number'];
                $User->employee_id = $form->getData()['employee_id'];
                $User->save();
            }

            $Enrollment = new Enrollment();
            $Enrollment->Session()->associate($Session);
            $Enrollment->User()->associate($this->getUser());
            $Enrollment->cost_center     = $form->getData()['cost_center'];
            $Enrollment->manager_name    = $form->getData()['manager_name'];
            $Enrollment->manager_contact = $form->getData()['manager_contact'];
            $Enrollment->note_user       = $form->getData()['note_user'];
            $Enrollment->cost            = $Session->Course->cost_employee;
            $Enrollment->status          = 'pending';
            $Enrollment->payment_method  = 'unit';
            $Enrollment->save();

            $this->logUserAction($this->getUser(), 'enrolled_with_bill_to_unit', $request, [
                'cost_center' => $form->getData()['cost_center'],
                'enrollment_id' => $Enrollment->id
            ]);

            $EmailSender = $this->get('app.email_sender');
            $EmailSender->send('student_new_enrollment_pending', $this->getUser(), ['Enrollment' => $Enrollment]);

            return $this->redirectToRoute('enrollment_confirmation', ['hash' => $Enrollment->hash]);
        }

        $data = [
            'Program'     => $Program,
            'Course'      => $Course,
            'Session'     => $Session,
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'Programs'                                 => $this->get('router')->generate('programs'),
                $Program->name                             => $this->get('router')->generate('courses', ['program_slug' => $program_slug]),
                $Course->name                              => $this->get('router')->generate('course_detail', ['program_slug' => $program_slug, 'course_slug' => $Course->slug]),
                date('l, F j', strtotime($Session->date1)) => $this->get('router')->generate('enroll', ['program_slug' => $program_slug, 'course_slug' => $Course->slug, 'session_id' => $Session->id]),
            ],
        ];

        return $this->render('classes/enroll_bill_to_unit.html.twig', $data);
    }

    /**
     * @Route("/my-classes", name="my_classes")
     * @Security("has_role('ROLE_USER')")
     */
    public function myClassesAction(Request $request)
    {
        $UpcomingEnrollments = Enrollment::join('sessions', 'sessions.id', '=', 'enrollments.session_id')
            ->with('Session', 'Session.Course', 'Session.Location', 'Session.Course.Program')
            ->where('user_id', $this->getUser()->id)
            ->where('sessions.date1', '>=', Capsule::raw('NOW()'))
            ->orderBy('sessions.date1', 'ASC')
            ->whereIn('status', ['pending', 'registered'])
            ->get();

        $PastEnrollments = Enrollment::join('sessions', 'sessions.id', '=', 'enrollments.session_id')
            ->with('Session', 'Session.Course', 'Session.Location', 'Session.Course.Program')
            ->where('user_id', $this->getUser()->id)
            ->where('sessions.date1', '<', Capsule::raw('NOW()'))
            ->orderBy('sessions.date1', 'DESC')
            ->whereIn('status', ['registered'])
            ->get();

        $data = [
            'sets'        => [
                [
                    'type'        => 'upcoming',
                    'name'        => 'Upcoming Classes',
                    'Enrollments' => $UpcomingEnrollments,
                ],
                [
                    'type'        => 'past',
                    'name'        => 'Past Classes',
                    'Enrollments' => $PastEnrollments,
                ],
            ],
            'breadcrumbs' => [
                'My Classes' => $this->get('router')->generate('my_classes'),
            ],
        ];

        return $this->render('classes/my_classes.html.twig', $data);
    }

    /**
     * @Route("/assigned-classes", name="assigned_classes")
     * @Security("has_role('ROLE_INSTRUCTOR')")
     */
    public function myAssignedClassesAction(Request $request)
    {
        $InstructorAssignments = InstructorAssignment::join('sessions', 'sessions.id', '=', 'instructor_assignment.session_id')
            ->with('Session', 'Session.Course', 'Session.Location', 'Session.Course.Program')
            ->where('user_id', $this->getUser()->id)
            ->orderBy('sessions.date1', 'ASC')
            ->get();

        $UpcomingInstructorAssignments = $InstructorAssignments->filter(function ($InstructorAssignment) {
            return !$InstructorAssignment->isPast();
        });

        $PastInstructorAssignments = $InstructorAssignments->filter(function ($InstructorAssignment) {
            return $InstructorAssignment->isPast();
        })->reverse();

        $data = [
            'sets'        => [
                [
                    'type'                  => 'upcoming',
                    'name'                  => 'Upcoming Classes',
                    'InstructorAssignments' => $UpcomingInstructorAssignments,
                ],
                [
                    'type'                  => 'past',
                    'name'                  => 'Past Classes',
                    'InstructorAssignments' => $PastInstructorAssignments,
                ],
            ],
            'breadcrumbs' => [
                'Assigned Classes' => $this->get('router')->generate('assigned_classes'),
            ],
        ];

        return $this->render('classes/my_assignments.html.twig', $data);
    }

    /**
     * @Route("/confirmation/{hash}", name="enrollment_confirmation")
     * @Security("has_role('ROLE_USER')")
     */
    public function enrollmentConfirmationAction(Request $request, $hash)
    {
        $Enrollment = Enrollment::with('Session', 'Session.Course')->where('hash', $hash)->first();

        if (!$Enrollment) {
            throw $this->createNotFoundException('Enrollment not found');
        }

        $data = [
            'Enrollment'  => $Enrollment,
            'breadcrumbs' => [
                'Confirmation' => $this->get('router')->generate('enrollment_confirmation', ['hash' => $Enrollment->hash]),
            ],
        ];

        return $this->render('classes/confirmation.html.twig', $data);
    }

    /**
     * @Route("/confirmation/{hash}/cancel", name="enrollment_cancellation")
     * @Security("has_role('ROLE_USER')")
     */
    public function enrollmentCancellationAction(Request $request, $hash)
    {
        $Enrollment = Enrollment::with('Session', 'Session.Course')->where('hash', $hash)->where('user_id', $this->getUser()->id)->first();

        if (!$Enrollment) {
            throw $this->createNotFoundException('Enrollment not found');
        }

        if (!$Enrollment->canBeCancelled()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createFormBuilder()
            ->add('reason', ChoiceType::class, [
                'choices'     => ['Select your reason for cancellation' => null] + array_flip(Session::$cancellation_reasons),
                'constraints' => [new NotBlank()],
            ])
            ->add('cancel_class', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($Enrollment->payment_method == 'unit' && $Enrollment->status == 'pending') {
                $Enrollment->setAttribute('status', 'cancelled');
            } else {
                $Enrollment->setAttribute('status', 'cancel_pending');
            }

            $Enrollment->setAttribute('cancellation_reason', $form->getData()['reason']);
            $Enrollment->Save();

            $this->logUserAction($this->getUser(), 'enrollment_cancelled', $request, []);

            $EmailSender = $this->get('app.email_sender');
            $EmailSender->send('student_enrollment_cancelled_student', $this->getUser(), ['Enrollment' => $Enrollment]);

            $this->addFlash('success', 'Your class has been cancelled');

            return $this->redirectToRoute('my_classes');
        }

        $data = [
            'form'        => $form->createView(),
            'Enrollment'  => $Enrollment,
            'breadcrumbs' => [
                'Confirmation' => $this->get('router')->generate('enrollment_confirmation', ['hash' => $Enrollment->hash]),
                'Cancellation' => $this->get('router')->generate('enrollment_cancellation', ['hash' => $Enrollment->hash]),
            ],
        ];

        return $this->render('classes/cancellation.html.twig', $data);
    }

//    public function OLDenrollWithCreditCardAction(Request $request, $program_slug, $course_slug, $session_id)
//    {
//        die('Do not use this method');
//
//        $Program = Program::where('slug', $program_slug)->get()->first();
//
//        if (!$Program) {
//            throw $this->createNotFoundException('Program not found');
//        }
//
//        $Course = Course::where('slug', $course_slug)->get()->first();
//
//        if ($Course->program_id != $Program->id) {
//            throw $this->createNotFoundException('Course not found');
//        }
//
//        $Session = Session::find($session_id);
//
//        $EnrollmentBouncer = $this->get('app.enrollment_bouncer');
//
//        if (!$EnrollmentBouncer->check($this->getUser(), $Session)) {
//            $this->addFlash('error', $EnrollmentBouncer->getError());
//
//            return $this->redirectToRoute('course_detail', ['program_slug' => $Program->slug, 'course_slug' => $Course->slug]);
//        }
//
//        $form = $this->createFormBuilder()
//            ->add('phone_number', TextType::class, [
//                'data'        => $this->getUser()->phone,
//                'required'    => true,
//                'constraints' => [new NotBlank()],
//            ])
//            ->add('billing_name', TextType::class)
//            ->add('credit_card', TextType::class)
//            ->add('expiration_month', TextType::class)
//            ->add('expiration_year', TextType::class)
//            ->add('enroll', SubmitType::class)
//            ->getForm();
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            if ($form->getData()['phone_number'] != '') {
//                $User        = $this->getUser();
//                $User->phone = $form->getData()['phone_number'];
//                $User->save();
//            }
//
//            $Enrollment = new Enrollment();
//            $Enrollment->Session()->associate($Session);
//            $Enrollment->User()->associate($this->getUser());
//            $Enrollment->cost = $this->getUser()->isEmployee() ? $Session->Course->cost_employee : $Session->Course->cost_public;
//
//            $data = $form->getData();
//
//            $TrustCommerce = new TrustCommerce();
//            $TrustCommerce->setAmount($Enrollment->cost);
//            $TrustCommerce->setBillingInfo($data['credit_card'], $data['expiration_month'], $data['expiration_year']);
//
//            $result = $TrustCommerce->send();
//
//            if ($result['status'] === "approved") {
//                $Enrollment->transid        = $result['transid'];
//                $Enrollment->payment_method = 'card';
//                $Enrollment->status         = 'registered';
//                $Enrollment->card_number    = substr($data['credit_card'], -4);
//                $Enrollment->save();
//
//                $EmailSender = $this->get('app.email_sender');
//                $EmailSender->send('student_new_enrollment_registered_credit_card', $this->getUser(), ['Enrollment' => $Enrollment]);
//
//                return $this->redirectToRoute('enrollment_confirmation', ['hash' => $Enrollment->hash]);
//            }
//
//            $Enrollment->delete();
//
//            $form->addError(new FormError('There was an error processing your credit card. Try again.'));
//        }
//
//        $data = [
//            'Program'     => $Program,
//            'Course'      => $Course,
//            'Session'     => $Session,
//            'form'        => $form->createView(),
//            'breadcrumbs' => [
//                'Programs'                                 => $this->get('router')->generate('programs'),
//                $Program->name                             => $this->get('router')->generate('courses', ['program_slug' => $program_slug]),
//                $Course->name                              => $this->get('router')->generate('course_detail', ['program_slug' => $program_slug, 'course_slug' => $Course->slug]),
//                date('l, F j', strtotime($Session->date1)) => $this->get('router')->generate('enroll', ['program_slug' => $program_slug, 'course_slug' => $Course->slug, 'session_id' => $Session->id]),
//            ],
//        ];
//
//        return $this->render('classes/enroll.html.twig', $data);
//    }
}
