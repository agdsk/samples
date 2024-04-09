<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use Carbon\Carbon;

class CertificationsController extends BaseController
{
    /**
     * @Route("/my-certifications", name="my_certifications")
     * @Security("has_role('ROLE_USER')")
     */
    public function indexAction(Request $request)
    {
        $UserCertifications = $this->getUser()->UserCertifications()->with('Certification')->orderBy('expires_at', 'ASC')->get();

        $CurrentCertifications = $UserCertifications->filter(function ($Certification) {
            return !Carbon::parse($Certification->expires_at)->isPast();
        });

        $ExpiredCertifications = $UserCertifications->filter(function ($Certification) {
            return Carbon::parse($Certification->expires_at)->isPast();
        })->reverse();

        $data = [
            'CurrentCertifications' => $CurrentCertifications,
            'ExpiredCertifications' => $ExpiredCertifications,
            'breadcrumbs'           => [
                'My Certifications' => $this->get('router')->generate('my_certifications'),
            ],
        ];

        return $this->render('certifications/my_certifications.html.twig', $data);
    }
}
