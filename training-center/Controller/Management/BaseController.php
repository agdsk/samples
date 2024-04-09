<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseController extends \AppBundle\Controller\BaseController
{
    public $name_plural   = '';

    public $name_singular = '';

    public $deletable     = false;

    public function indexQuery()
    {
        throw new \Exception('Define indexQuery()');
    }

    public function createQuery()
    {
        throw new \Exception('Define createQuery()');
    }

    public function editQuery($id)
    {
        throw new \Exception('Define recordQuery()');
    }

    protected function preSave(Request $request, $Record)
    {
        return $Record;
    }

    public function indexAction(Request $request)
    {
        $Records = $this->indexQuery();
        $Records = $Records->limit(1000, 0);
        $Records = $Records->get();

        $data = [
            'Records'       => $Records,
            'columns'       => $this->indexColumns(),
            'name_plural'   => $this->name_plural,
            'name_singular' => $this->name_singular,
            'deletable'     => $this->deletable,
            'breadcrumbs'   => [
                'System Management' => $this->get('router')->generate('management'),
                'View'              => '',
            ],
        ];

        return $this->render('admin/management/index.html.twig', $data);
    }

    public function createAction(Request $request)
    {
        $Record = $this->createQuery();

        $form = $this->editForm($Record);

        $form->add('save', SubmitType::class);
        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Record = $this->preSave($request, $Record);

            $Record->save();

            $class_name = str_replace("appbundle\\model\\", "", strtolower(get_class($Record)));
            $this->logUserAction($this->getUser(), 'admin_created_' . $class_name, $request, [
                $class_name . '_id' => $Record->id
            ]);

            return $this->redirect('../');
        }

        $data = [
            'Record'        => $Record,
            'form'          => $form->createView(),
            'name_singular' => $this->name_singular,
            'breadcrumbs'   => [
                'System Management' => $this->get('router')->generate('management'),
                'Create'            => '',
            ],
        ];

        return $this->render('admin/management/create.html.twig', $data);
    }

    public function editAction(Request $request, $id)
    {
        $Record = $this->editQuery($id);

        $form = $this->editForm($Record);

        $form->add('save', SubmitType::class);
        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Record = $this->preSave($request, $Record);

            $Record->save();

            $class_name = str_replace("appbundle\\model\\", "", strtolower(get_class($Record)));
            $this->logUserAction($this->getUser(), 'admin_updated_' . $class_name, $request, [
                $class_name . '_id' => $Record->id
            ]);

            return $this->redirect('../');
        }

        $data = [
            'Record'        => $Record,
            'form'          => $form->createView(),
            'name_singular' => $this->name_singular,
            'breadcrumbs'   => [
                'System Management' => $this->get('router')->generate('management'),
                'Edit'              => 'Edit',
            ],
        ];

        return $this->render('admin/management/edit.html.twig', $data);
    }
}
