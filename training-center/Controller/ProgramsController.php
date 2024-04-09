<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Model\Program;

class ProgramsController extends BaseController
{
    /**
     * @Route("/programs", name="programs")
     */
    public function indexAction(Request $request)
    {
        $data = [
            'Programs'    => Program::all(),
            'breadcrumbs' => [
                'Programs' => $this->get('router')->generate('programs'),
            ],
        ];

        return $this->render('programs/index.html.twig', $data);
    }
}
