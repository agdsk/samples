<?php

namespace AppBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Invoice extends BaseModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $number;
}