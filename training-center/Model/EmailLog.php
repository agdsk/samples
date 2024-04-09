<?php

namespace AppBundle\Model;

use Carbon\Carbon;

class EmailLog extends BaseModel
{
    protected $table      = "email_log";

    public    $timestamps = true;

    public function User()
    {
        return $this->belongsTo('AppBundle\Model\User');
    }
}
