<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Program;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/programs")
 */
class ProgramManagerController extends BaseController
{
    public $name_plural   = 'Programs';

    public $name_singular = 'Program';

    public function indexQuery()
    {
        return Program::orderBy('name', 'ASC');
    }

    public function createQuery()
    {
        return new Program();
    }

    public function editQuery($id)
    {
        return Program::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('overview', TextareaType::class, [
                'required' => false,
            ]);
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
