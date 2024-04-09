<?php namespace App\Models;

use App\Jobs\SendCancellationEmail;
use App\Jobs\SendCancellationText;
use App\Jobs\SendCustomerCancellationEmail;
use Illuminate\Database\Eloquent\Model;
use function App\dispatch;
use function App\env;

class Reservation extends Model
{
    protected $attributes = [
        'status' => 0,
    ];

    public static $available_statuses = [
        -1 => 'Cancelled',
        0  => 'Pending',
        1  => 'Checked In',
        2  => 'Complete',
    ];

    protected $casts = [
        'checkin' => 'bool',
        'demo'    => 'bool',
    ];

    // -----------------------------------------------------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------------------------------------------------

    public function getUrlAttribute()
    {
        return env('APP_CONSUMER_SITE') . '/demo/' . $this->location_id . '/confirmation/' . $this->hash;
    }

    // cancelled|pending|checked in|complete
    public function getStatusSlugAttribute()
    {
        return strtolower(str_replace(' ', '_', self::$available_statuses[$this->status]));
    }

    // 3:00 PM PDT on Friday, April 29, 2016
    public function getFullFriendlyDateAndTimeAttribute()
    {
        return $this->DateTime()->format('l, F j, Y \a\t g:i A T');
    }

    // Aaron G
    public function getSafeNameAttribute()
    {
        return $this->first_name . ' ' . substr($this->last_name, 0, 1);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Psuedo Accessors
    // -----------------------------------------------------------------------------------------------------------------

    public function DateTime()
    {
        return new \DateTime($this->date . ' ' . Location::toTime($this->time), new \DateTimeZone($this->Location->timezone));
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Operations
    // -----------------------------------------------------------------------------------------------------------------

    public function Save(array $options = [])
    {
        if ($this->hash == '') {
            $this->hash = bin2hex(openssl_random_pseudo_bytes(16));
        }

        return parent::save($options);
    }

    public function customerCancel()
    {
        $this->status = -1;
        $this->save();

        dispatch(new SendCustomerCancellationEmail($this));
    }

    public function cancel()
    {
        $this->status = -1;
        $this->save();

        dispatch(new SendCancellationEmail($this));
        dispatch(new SendCancellationText($this));
    }

    public function checkin()
    {
        if ($this->status <= 1) {
            $this->status = 1;
            $this->save();
        }
    }

    public function complete()
    {
        $this->status = 2;
        $this->save();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------------------------------------------------

    public function Location()
    {
        return $this->belongsTo('App\Models\Location');
    }
}
