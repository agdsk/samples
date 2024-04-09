<?php namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use function App\asset;
use function App\bcrypt;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    public static $available_statuses = [
        0 => 'Deactivated',
        1 => 'Active',
    ];

    public static $roles = [
        10 => 'Ambassador',
        20 => 'Manager',
        30 => 'Administrator',
    ];

    protected $fillable = ['first_name', 'last_name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'role'   => 10,
        'status' => 1,
    ];

    public function isAmbassador()
    {
        return $this->role >= 10;
    }

    public function isManager()
    {
        return $this->role >= 20;
    }

    public function isAdmin()
    {
        return $this->role >= 30;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Mutators
    // -----------------------------------------------------------------------------------------------------------------

    public function setPasswordAttribute($password)
    {
        if ($password == '') {
            return;
        }

        $this->attributes['password'] = bcrypt($password);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------------------------------------------------

    public function getStatusImageAttribute()
    {
        return $this->status > 0 ? asset('images/dot-green.png') : asset('images/dot-grey.png');
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getRoleNameAttribute()
    {
        return self::$roles[$this->role];
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------------------------------------------------

    public function Locations()
    {
        return $this->belongsToMany('App\Models\Location')->with('Brand');
    }
}
