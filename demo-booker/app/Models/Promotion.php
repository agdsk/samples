<?php namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $appends = [
        'expired',
    ];

    protected $attributes = [
        'size'  => 1,
        'limit' => 1,
    ];

    public function validOn($year, $month, $day)
    {
        $targetDate = Carbon::create($year, $month, $day, 0, 0, 0, 'Europe/London');

        list ($year, $month, $day) = explode('-', $this->start);

        $start = Carbon::create($year, $month, $day, 0, 0, 0, 'Europe/London');

        list ($year, $month, $day) = explode('-', $this->end);

        $end = Carbon::create($year, $month, $day, 0, 0, 0, 'Europe/London');

        if ($targetDate->between($start, $end)) {
            return true;
        }

        return false;
    }

    public function getExpiredAttribute()
    {
        list($year, $month, $day) = explode('-', $this->end);

        $date = Carbon::createFromDate($year, $month, $day);

        return $date->isPast();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------------------------------------------------

    public function Location()
    {
        return $this->belongsTo('App\Models\Location');
    }
}
