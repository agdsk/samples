<?php

namespace AppBundle\Model;

use Carbon\Carbon;
use Symfony\Component\Validator\Constraints as Assert;

class Enrollment extends BaseModel
{
    protected $fillable   = ['user_id', 'session_id'];

    public    $timestamps = true;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $session_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $user_id;

    /**
     * @Assert\Type(type="string")
     */
    protected $hash;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices = {"unit", "card", "invoice"})
     */
    protected $payment_method;

    /**
     * @Assert\Type(type="string")
     */
    protected $cost_center;

    /**
     * @Assert\Type(type="string")
     */
    protected $manager_name;

    /**
     * @Assert\Type(type="string")
     */
    protected $manager_contact;

    /**
     * @Assert\Type(type="string")
     */
    protected $card_number;

    /**
     * @Assert\Type(type="string")
     */
    protected $transid;

    /**
     * @Assert\Type(type="numeric")
     */
    protected $invoice_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $cost;

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     */
    protected $cost2;

    /**
     * @Assert\Type(type="integer")
     */
    protected $book_id;

    /**
     * @Assert\NotBlank()
     * @Assert\Choice(choices = {"pending", "registered", "cancel_pending", "cancelled", "denied"})
     */
    protected $status;

    /**
     * @Assert\Choice(choices = {"pass", "fail"})
     */
    protected $grade;

    /**
     * @Assert\Choice(choices = {"show", "noshow"})
     */
    protected $attendance;

    /**
     * @Assert\Type(type="integer")
     */
    protected $cancellation_reason;

    /**
     * @Assert\Type(type="string")
     */
    protected $note_admin;

    /**
     * @Assert\Type(type="string")
     */
    protected $note_user;

    public function Session()
    {
        return $this->belongsTo('AppBundle\Model\Session');
    }

    public function User()
    {
        return $this->belongsTo('AppBundle\Model\User');
    }

    public function Invoice()
    {
        return $this->belongsTo('AppBundle\Model\Invoice');
    }

    public function save(array $options = [])
    {
        if (empty($this->hash)) {

            $this->setAttribute('hash', md5(uniqid($this->User->id, true)));
        }

        return parent::save($options);
    }

    public function getCancellationReasonFriendlyAttribute()
    {
        if ($this->cancellation_reason == '') {
            return '';
        }

        return Session::$cancellation_reasons[$this->cancellation_reason];
    }

    public function canBeCancelled()
    {
        $DateTime = new Carbon($this->Session->date1 . ' ' . $this->Session->start_time1, new \DateTimeZone('America/New_York'));

        $diff = $DateTime->diffInHours(Carbon::now(), false);

        if ($diff >= 24) {
            return false;
        }

        if ($this->attendance == 'show') {
            return false;
        }

        if ($this->payment_method == 'invoice') {
            return false;
        }

        return true;
    }
}
