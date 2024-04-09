<?php

namespace AppBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Location extends BaseModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $name;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $slug;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $address1;

    /**
     * @Assert\Type(type="string")
     */
    protected $address2;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $city;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $state;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $zip;

    /**
     * @Assert\Type(type="string")
     */
    protected $specifics;

    public function getGoogleMapUrlAttribute()
    {
        return 'https://www.google.com/maps?q=' . $this->address1 . '+' . $this->address2 . '+' . $this->city . '+' . $this->state . '+' . $this->zip;
    }
}
