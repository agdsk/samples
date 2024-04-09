<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Model\Enrollment;
use AppBundle\Model\Invoice;
use AppBundle\Model\Session;
use AppBundle\Model\User;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkEnrollController extends BaseController
{
    /**
     * @Route("/bulk-enroll/{session_id}", name="bulk_enroll")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function bulkEnrollAction(Request $request, $session_id)
    {
        $Session = Session::find($session_id);

        $form = $this->createFormBuilder()
            ->add('file', FileType::class, ['label' => 'Choose File'])
            ->add('upload_csv', SubmitType::class, ['label' => 'Upload CSV'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logUserAction($this->getUser(), 'admin_bulk_enrolled', $request, []);

            $formData = $form->getData();
            $file     = $formData['file'];

            ini_set('auto_detect_line_endings', true);

            if ($file == null) {
                $this->addFlash('error', 'No CSV file was uploaded.');

                return $this->redirectToRoute('bulk_enroll', ['session_id' => $session_id]);
            }

            if ($file->getMimeType() != 'text/csv' && $file->getMimeType() != 'text/plain') {
                $this->addFlash('error', 'The file must be a CSV');

                return $this->redirectToRoute('bulk_enroll', ['session_id' => $session_id]);
            }

            $csv = array_map('str_getcsv', file($file->getRealPath()));

            $Users = collect();
            foreach ($csv as $key => $value) {
                if ($key > 0 && $value[2] !== '') {
                    $User = User::where('email', $value[2])->first();
                    if ($User == null) {
                        $User             = new User();
                        $User->first_name = $value[0];
                        $User->last_name  = $value[1];
                        $User->email      = $value[2];
                        $User->regenerateLoginToken();
                        $User->save();

                        $EmailSender = $this->get('app.email_sender');
                        $EmailSender->send('user_new_bulk', $User);
                    }

                    $Users->push($User);
                }
            }

            foreach ($Users as $User) {

                $EnrollmentBouncer = $this->get('app.enrollment_bouncer');

                if (!$EnrollmentBouncer->check($User, $Session)) {

                    $this->addFlash('error', $User->display_name . ' can not be enrolled in this session: ' . $EnrollmentBouncer->getError());

                    return $this->redirectToRoute('bulk_enroll', ['session_id' => $session_id]);
                }
            }

            $Invoice = new Invoice();
            $Invoice->save();

            foreach ($Users as $User) {
                $Enrollment = new Enrollment();
                $Enrollment->User()->associate($User);
                $Enrollment->Session()->associate($Session);
                $Enrollment->Invoice()->associate($Invoice);
                $Enrollment->cost = $Enrollment->Session->Course->cost_employee;
                $Enrollment->Save();

                $EmailSender = $this->get('app.email_sender');
                $EmailSender->send('student_new_enrollment_registered_bulk', $User, ['Enrollment' => $Enrollment]);
            }

            $this->addFlash('success', 'The users were successfully enrolled');

            return $this->redirectToRoute('admin_session_overview', ['session_id' => $session_id]);
        }

        $data = [
            'session_id'  => $session_id,
            'form'        => $form->createView(),
            'breadcrumbs' => [
                'Bulk Enroll' => $this->get('router')->generate('bulk_enroll', ['session_id' => $session_id]),
            ],
        ];

        return $this->render('admin/session-overview/bulk_enroll.html.twig', $data);
    }

    /**
     * @Route("/bulk-upload-template", name="bulk_upload_template")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function bulkUploadTemplateAction(Request $request)
    {
        $this->logUserAction($this->getUser(), 'admin_bulk_enrolled', $request, []);

        $response = new StreamedResponse();
        $response->setCallback(function () {
            $fp = fopen('php://output', 'w');

            fputcsv($fp, ['First Name', 'Last Name', 'Email']);

            fputcsv(
                $fp, [
                    'John',
                    'Smith',
                    'john@example.com',
                ]
            );

            fputcsv(
                $fp, [
                    'Jane',
                    'Doe',
                    'jane@example.com',
                ]
            );

            fclose($fp);
        });

        $filename = 'FH_AHA_Bulk_Enrollment_Template.csv';

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
}
