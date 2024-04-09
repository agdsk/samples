<?php

namespace AppBundle\Controller\Management;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Model\Location;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/locations")
 */
class LocationManagerController extends BaseController
{
    public $name_plural   = 'Locations';

    public $name_singular = 'Location';

    public function indexQuery()
    {
        return Location::orderBy('id');
    }

    public function createQuery()
    {
        return new Location();
    }

    public function editQuery($id)
    {
        return Location::find($id);
    }

    public function editForm($Record = null)
    {
        return $this->createFormBuilder($Record)
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('address1', TextType::class)
            ->add('address2', TextType::class)
            ->add('city', TextType::class)
            ->add('state', TextType::class)
            ->add('zip', TextType::class)
            ->add('specifics', TextType::class);
    }

    public function indexColumns()
    {
        return [
            'Name'      => ['field' => 'name'],
            'Slug'      => ['field' => 'slug'],
            'Address 1' => ['field' => 'address1'],
            'Address 2' => ['field' => 'address2'],
            'City'      => ['field' => 'city'],
            'State'     => ['field' => 'state'],
            'Zip'       => ['field' => 'zip'],
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
