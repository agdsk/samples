<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Invoice;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/invoices")
 */
class InvoiceManagerController extends BaseController
{
    public $name_plural   = 'Invoices';

    public $name_singular = 'Invoice';

    public function indexQuery()
    {
        return Invoice::orderBy('id');
    }

    public function createQuery()
    {
        return new Invoice();
    }

    public function editQuery($id)
    {
        return Invoice::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('number', IntegerType::class);
    }

    public function indexColumns()
    {
        return [
            'Number' => ['field' => 'number'],
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
