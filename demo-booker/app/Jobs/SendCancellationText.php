<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Services_Twilio;

class SendCancellationText extends ReservationCommunicationJob implements ShouldQueue
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

            $message = "Due to an unforeseen issue on our end, we have to reschedule your Rift demo. Please accept our sincere apologies. Choose a new time at live.acme.com so we can get everything going for you again. - The Acme Team";

            $client->account->messages->sendMessage(env('TWILIO_NUMBER'), $this->Reservation->text, $message);
        }
    }
}
