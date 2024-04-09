<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Override extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------------------------------------------------

    public function Location()
    {
        return $this->belongsTo('App\Models\Location');
    }
}
