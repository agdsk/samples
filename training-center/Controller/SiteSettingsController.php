<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use AppBundle\Model\SiteSettings;

class SiteSettingsController extends BaseController
{
    /**
     * @Route("/admin/site-settings", name="site_settings")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $query = Capsule::select("SELECT COUNT(*) as count FROM site_settings");
        if ($query[0]->count < 1) {
            $SiteSettings = new SiteSettings();
        } else {
            $SiteSettings = SiteSettings::get()->first();
        }

        $form = $this->createFormBuilder()
            ->add('notice', TextareaType::class, [
                'data' => $SiteSettings->notice,
                'required' => false,
            ])
            ->add('update', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logUserAction($this->getUser(), 'admin_added_notice', $request, [
                'user_id' => $User->id
            ]);

            $notice = $form->getData()['notice'];

            $SiteSettings->notice = $notice;
            $SiteSettings->save();

            $this->addFlash('success', 'The notice has been updated');
            return $this->redirectToRoute('site_settings');
        }

        $data['form'] = $form->createView();

        $data['breadcrumbs'] = [
            'Site Settings' => $this->get('router')->generate('site_settings'),
        ];

        return $this->render('admin/site-settings/index.html.twig', $data);
    }
}
