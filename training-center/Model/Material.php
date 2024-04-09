<?php

namespace AppBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Material extends BaseModel
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
    protected $isbn10;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $isbn13;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $rentable;

    /**
     * @Assert\Type(type="numeric")
     */
    protected $rent_cost;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $purchase_cost;

    public function Courses()
    {
        return $this->belongsToMany('AppBundle\Model\Course');
    }

    public function getIsbnSimpleAttribute()
    {
        return str_replace('-', '', $this->isbn10);
    }
}
