<?php

namespace AppBundle\Model;

use Carbon\Carbon;

use Symfony\Component\Validator\Constraints as Assert;

class UserCertification extends BaseModel
{
    protected $table = "user_certifications";

    protected $fillable = ['user_id', 'certification_id'];

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $user_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $certification_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */
    protected $issued_at;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     */

    protected $expires_at;

    public function User()
    {
        return $this->belongsTo('AppBundle\Model\User');
    }

    public function Certification()
    {
        return $this->belongsTo('AppBundle\Model\Certification');
    }

    public function Session()
    {
        return $this->belongsTo('AppBundle\Model\Session');
    }

    public function isExpired()
    {
        $DateTime = new Carbon($this->expires_at);

        return $DateTime->isPast();
    }

    public function daysUntilExpired()
    {
        $now     = Carbon::now();
        $expires = new Carbon($this->expires_at);

        return $now->diffInDays($expires, false);
    }
}
