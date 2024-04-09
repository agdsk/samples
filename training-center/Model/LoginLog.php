<?php

namespace AppBundle\Model;

use Carbon\Carbon;

class LoginLog extends BaseModel
{
    protected $table      = "login_log";

    public    $timestamps = true;
}
