<?php

namespace AppBundle\Model;

use Carbon\Carbon;

use Symfony\Component\Validator\Constraints as Assert;

use Illuminate\Database\Capsule\Manager as Capsule;

class Session extends BaseModel
{
    protected     $fillable             = ['course_id', 'date1', 'start_time1'];

    public static $cancellation_reasons = [
        10 => 'Did Not Meet Requirements',
        20 => 'Timing Conflicted with Schedule',
        30 => 'Signed up for Wrong Course',
        40 => 'Forgot Class Materials',
        50 => 'Other',
    ];

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $course_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $location_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $date1;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $start_time1;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $end_time1;

    /**
     * @Assert\Type(type="string")
     */
    protected $date2;

    /**
     * @Assert\Type(type="string")
     */
    protected $start_time2;

    /**
     * @Assert\Type(type="string")
     */
    protected $end_time2;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $student_max;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $instructor_max;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $online_registration;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $public;

    /**
     * @Assert\Type(type="string")
     */
    protected $notice;

    public function Course()
    {
        return $this->belongsTo('AppBundle\Model\Course');
    }

    public function Location()
    {
        return $this->belongsTo('AppBundle\Model\Location');
    }

    public function InstructorAssignments()
    {
        return $this->hasMany('AppBundle\Model\InstructorAssignment');
    }

    public function Enrollments()
    {
        return $this->hasMany('AppBundle\Model\Enrollment');
    }

    public function Users()
    {
        return $this->hasManyThrough('AppBundle\Model\User', 'AppBundle\Model\Enrollment');
    }

    public function isPast()
    {
        $DateTime = new Carbon($this->date1);

        return $DateTime->isPast();
    }

    public function isToday()
    {
        $DateTime = new Carbon($this->date1);

        return $DateTime->isToday();
    }

    public static function dropdown($key = 'name')
    {
        $choices = [];

        foreach (Capsule::select('SELECT sessions.id as session_id,date1,courses.name as course_name FROM sessions LEFT JOIN courses ON sessions.course_id = courses.id ORDER BY course_name,date1 ASC') as $Session) {
            $choices[$Session->course_name . ' ' . $Session->date1 . ' (' . $Session->session_id . ')'] = $Session->session_id;
        }

        return $choices;
    }

    public function save(array $options = [])
    {
        if ($this->date2 == '') {
            $this->setAttribute('date2', null);
        }

        if ($this->start_time2 == '00:00:00' || $this->start_time2 == '') {
            $this->setAttribute('start_time2', null);
        }

        if ($this->end_time2 == '00:00:00' || $this->start_time2 == '') {
            $this->setAttribute('end_time2', null);
        }

        return parent::save($options);
    }
}
