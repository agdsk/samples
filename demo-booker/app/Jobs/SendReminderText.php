<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Services_Twilio;

class SendReminderText extends ReservationCommunicationJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public function __construct($Reservation)
    {
        $this->Reservation = $Reservation;
    }

    public function handle()
    {
        if ($this->Reservation->text != '') {
            $client = new Services_Twilio(env('TWILIO_SID'), env('TWILIO_TOKEN'));

            $message = "Your Product demo is tomorrow! We'll see you at " . $this->Reservation->full_friendly_date_and_time . " at " . $this->Reservation->Location->name . ", " . $this->Reservation->Location->full_friendly_address;

            $client->account->messages->sendMessage(env('TWILIO_NUMBER'), $this->Reservation->text, $message);
        }
    }
}
