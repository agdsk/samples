<?php

namespace AppBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Certification extends BaseModel
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
}
