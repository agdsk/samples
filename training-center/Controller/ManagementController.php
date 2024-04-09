<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class ManagementController extends BaseController
{
    /**
     * @Route("/management", name="management")
     */
    public function indexAction(Request $request)
    {
        $data = [
            'breadcrumbs' => [
                'System Management' => $this->get('router')->generate('management'),
            ],
        ];

        return $this->render('admin/management/list.html.twig', $data);
    }
}
