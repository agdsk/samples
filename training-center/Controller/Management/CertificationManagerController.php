<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Certification;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/certifications")
 */
class CertificationManagerController extends BaseController
{
    public $name_plural   = 'Certifications';

    public $name_singular = 'Certification';

    public function indexQuery()
    {
        return Certification::orderBy('name', 'ASC');
    }

    public function createQuery()
    {
        return new Certification();
    }

    public function editQuery($id)
    {
        return Certification::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('name', TextType::class)
            ->add('slug', TextType::class);
    }

    public function indexColumns()
    {
        return [
            'Name' => ['field' => 'name'],
            'Slug' => ['field' => 'slug'],
        ];
    }

    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * @Route("/create/")
     */
    public function createAction(Request $request)
    {
        return parent::createAction($request);
    }

    /**
     * @Route("/edit/{id}")
     */
    public function editAction(Request $request, $id)
    {
        return parent::editAction($request, $id);
    }
}
