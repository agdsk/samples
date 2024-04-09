<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function Locations()
    {
        return $this->hasMany('App\Models\Location');
    }

}
