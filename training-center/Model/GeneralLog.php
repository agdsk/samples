<?php

namespace AppBundle\Model;

use Carbon\Carbon;

class GeneralLog extends BaseModel
{
    protected $table      = "general_log";

    public    $timestamps = true;

    public function recordEvent($event_type, $data) {
        $this->setAttribute('event_type', $event_type);
        $this->setAttribute('data', json_encode($data));
    }

    public function User()
    {
        return $this->belongsTo('AppBundle\Model\User');
    }
}
