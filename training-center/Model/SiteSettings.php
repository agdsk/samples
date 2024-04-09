<?php

namespace AppBundle\Model;

use Carbon\Carbon;

class SiteSettings extends BaseModel
{
    protected $table      = "site_settings";

    /**
     * @Assert\Type(type="string")
     */
    protected $notice;
}
