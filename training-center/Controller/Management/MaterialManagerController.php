<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Material;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/materials")
 */
class MaterialManagerController extends BaseController
{
    public $name_plural   = 'Materials';

    public $name_singular = 'Material';

    public function indexQuery()
    {
        return Material::orderBy('Name', 'ASC');
    }

    public function createQuery()
    {
        return new Material();
    }

    public function editQuery($id)
    {
        return Material::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('name', TextType::class)
            ->add('isbn10', TextType::class)
            ->add('isbn13', TextType::class)
            ->add('rentable', ChoiceType::class, [
                'choices' => [
                    'Can not be rented' => 0,
                    'Can be rented'     => 1,
                ],
            ])
            ->add('rent_cost', MoneyType::class, [
                'currency' => false,
            ])
            ->add('purchase_cost', MoneyType::class, [
                'currency' => false,
            ]);
    }

    public function indexColumns()
    {
        return [
            'Name'    => ['field' => 'name'],
            'ISBN 10' => ['field' => 'isbn10'],
            'ISBN 13' => ['field' => 'isbn13'],
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
