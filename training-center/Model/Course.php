<?php

namespace AppBundle\Model;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Validator\Constraints as Assert;

use Carbon\Carbon;

class Course extends BaseModel
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

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $program_id;

    /**
     * @Assert\Type(type="numeric")
     */
    protected $certification_received_id;

    /**
     * @Assert\Type(type="numeric")
     */
    protected $certification_required_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $default_instructor_max;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $default_student_max;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $days;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $default_start_time1;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $default_end_time1;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $default_start_time2;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $default_end_time2;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $cost_public;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $cost_employee;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    protected $public;

    /**
     * @Assert\Type(type="string")
     */
    protected $overview;

    /**
     * @Assert\Type(type="string")
     */
    protected $description;

    /**
     * @Assert\Type(type="string")
     */
    protected $instruction_intended_audience;

    /**
     * @Assert\Type(type="string")
     */
    protected $instruction_prerequisites;

    /**
     * @Assert\Type(type="string")
     */
    protected $instruction_before_class;

    /**
     * @Assert\Type(type="string")
     */
    protected $instruction_bring_to_class;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices = {"and", "or"})
     */
    protected $and_or_materials;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices = {"0", "1"})
     */
    protected $rentable;

    public function Sessions()
    {
        return $this->hasMany('AppBundle\Model\Session')->orderBy('date1', 'ASC');
    }

    public function UpcomingPublicSessions()
    {
        $curtime = Carbon::now('America/New_York');

        if ($curtime->hour >= 16) { // 4pm
            return $this->hasMany('AppBundle\Model\Session')->orderBy('date1', 'ASC')->where('public', 1)->where('date1', '>=', Capsule::raw('DATE_ADD(CURDATE(), INTERVAL 2 DAY)'));
        } else {
            return $this->hasMany('AppBundle\Model\Session')->orderBy('date1', 'ASC')->where('public', 1)->where('date1', '>=', Capsule::raw('DATE_ADD(CURDATE(), INTERVAL 1 DAY)'));
        }
    }

    public function Program()
    {
        return $this->belongsTo('AppBundle\Model\Program');
    }

    public function CertificationReceived()
    {
        return $this->belongsTo('AppBundle\Model\Certification', 'certification_received_id');
    }

    public function CertificationRequired()
    {
        return $this->belongsTo('AppBundle\Model\Certification', 'certification_required_id');
    }

    public function Materials()
    {
        return $this->belongsToMany('AppBundle\Model\Material');
    }

    public function getBeforeClassArrayAttribute()
    {
        return $this->combineInstruction('instruction_before_class');
    }

    public function getIntendedAudienceArrayAttribute()
    {
        return $this->combineInstruction('instruction_intended_audience');
    }

    public function getPrerequisitesArrayAttribute()
    {
        return $this->combineInstruction('instruction_prerequisites', false);
    }

    public function getBringToClassArrayAttribute()
    {
        return $this->combineInstruction('instruction_bring_to_class');
    }

    private function combineInstruction($key, $include_program = true)
    {
        $program = $this->Program->$key;
        $course  = $this->$key;

        if ($include_program) {
            $combined = $program . "\n" . $course;
        } else {
            $combined = $course;
        }

        $combined = explode("\n", $combined);

        $combined = array_map(function ($item) {
            return trim($item, ' *-•');
        }, $combined);

        $combined = array_filter($combined);

        return $combined;
    }
}
