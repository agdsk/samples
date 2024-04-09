<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use Illuminate\Database\Capsule\Manager as Capsule;
use AppBundle\Model\SiteSettings;

class HomeController extends BaseController
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction(Request $request)
    {
        $query = Capsule::select("SELECT COUNT(*) as count FROM site_settings");
        if ($query[0]->count < 1) {
            $SiteSettings = new SiteSettings();
        } else {
            $SiteSettings = SiteSettings::get()->first();
        }

        return $this->render('homepage/index.html.twig', ['notice' => $SiteSettings->notice]);
    }

    /**
     * @Route("/terms-and-conditions", name="terms")
     */
    public function termsAction(Request $request)
    {
        $data = [
          'breadcrumbs' => [
            'Terms and Conditions' => $this->get('router')->generate('terms'),
          ],
        ];
        return $this->render('homepage/terms_and_conditions.html.twig', $data);
    }
}
