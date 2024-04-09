<?php

namespace AppBundle\Model;

use Carbon\Carbon;

class InstructorAssignment extends BaseModel
{
    protected $table = "instructor_assignment";

    public function Session()
    {
        return $this->belongsTo('AppBundle\Model\Session');
    }

    public function User()
    {
        return $this->belongsTo('AppBundle\Model\User');
    }

    public function isPast()
    {
        if ($this->date2 != null) {
            $DateTime = new Carbon($this->date2);
        } else {
            $DateTime = new Carbon($this->date1);
        }

        return $DateTime->isPast();
    }
}
