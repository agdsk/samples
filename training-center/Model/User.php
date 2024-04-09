<?php

namespace AppBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

use Symfony\Component\Validator\Constraints as Assert;

use Illuminate\Database\Capsule\Manager as Capsule;

use AppBundle\Model\LoginLog;

class User extends BaseModel implements UserInterface, EquatableInterface
{
    protected $fillable = ['email'];

    /**
     * @Assert\NotBlank()
     */
    protected $first_name;

    /**
     * @Assert\NotBlank()
     */
    protected $last_name;

    /**
     * @Assert\Type(type="string")
     */
    protected $opid;

    /**
     * @Assert\Type(type="string")
     */
    protected $department;

    /**
     * @Assert\Type(type="string")
     */
    protected $license_number;

    /**
     * @Assert\Type(type="string")
     */
    protected $title;

    /**
     * @Assert\Type(type="string")
     */
    protected $role;

    /**
     * @Assert\Type(type="string")
     */
    protected $phone;

    /**
     * @Assert\Type(type="string")
     */
    protected $employee_id;

    /**
     * @Assert\Type(type="string")
     */
    protected $address1;

    /**
     * @Assert\Type(type="string")
     */
    protected $address2;

    /**
     * @Assert\Type(type="string")
     */
    protected $city;

    /**
     * @Assert\Type(type="string")
     */
    protected $state;

    /**
     * @Assert\Type(type="string")
     */
    protected $zip;

    /**
     * @Assert\Type(type="string")
     */
    protected $country;

    protected $token;

    public static $blacklisted_domain_names = [
        'flhosp.org',
        'ahss.org'
    ];

    public static function isLocked($username)
    {
        $LoginLogCount = LoginLog::where('username', $username)
            ->where('success', false)
            ->where('created_at', '>', date("Y-m-d H:i:s", strtotime("-30 minutes")))
            ->count();

        if($LoginLogCount >= 5) {
            return true;
        }

        return false;
    }

    public function getRoles()
    {
        $roles = ['ROLE_USER'];

        if ($this->isEmployee()) {
            array_push($roles, 'ROLE_EMPLOYEE');
        }

        if ($this->isInstructor()) {
            array_push($roles, 'ROLE_INSTRUCTOR');
        }

        if ($this->isAdmin()) {
            array_push($roles, 'ROLE_INSTRUCTOR');
            array_push($roles, 'ROLE_ADMIN');
        }

        return $roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return '';
    }

    public function getUsername()
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        return $this->id === $user->id;
    }

    public function isEmployee()
    {
        return $this->opid != '';
    }

    public function isInstructor()
    {
        return $this->role == 'Instructor';
    }

    public function isAdmin()
    {
        return $this->role == 'Admin';
    }

    public function getDisplayNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function changePassword($password)
    {
        $encoder = $this->container->get('security.password_encoder');

        $this->attributes['password'] = $encoder->encodePassword($this, $password);
    }

    public function setPasswordAttribute($password)
    {
        if (substr($password, 0, 4) != '$2y$') {
            $encoder = $this->container->get('security.password_encoder');

            $this->attributes['password'] = $encoder->encodePassword($this, $password);
        }

        if ($password == null) {
            $this->attributes['password'] = null;
        }
    }

    public function Enrollments()
    {
        return $this->hasMany('AppBundle\Model\Enrollment');
    }

    public function InstructorAssignments()
    {
        return $this->hasMany('AppBundle\Model\InstructorAssignment');
    }

    public function UserCertifications()
    {
        return $this->hasMany('AppBundle\Model\UserCertification');
    }

    public function EmailLogs()
    {
        return $this->hasMany('AppBundle\Model\EmailLog');
    }

    public function regenerateLoginToken()
    {
        $token = md5(uniqid($this->id, true));

        $this->attributes['token'] = $token;
        $this->token               = $token;
    }

    public static function dropdown($key = 'name')
    {
        $user_choices = [];

        foreach (Capsule::select('SELECT id,first_name,last_name FROM users ORDER BY last_name, first_name') as $User) {
            $user_choices[$User->last_name . ', ' . $User->first_name . ' (' . $User->id . ')'] = $User->id;
        }

        return $user_choices;
    }
}
