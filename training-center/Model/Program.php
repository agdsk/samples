<?php

namespace AppBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Program extends BaseModel
{
    /**
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @Assert\Type(type="string")
     */
    protected $overview;

    /**
     * @Assert\NotBlank()
     */
    protected $slug;
}
