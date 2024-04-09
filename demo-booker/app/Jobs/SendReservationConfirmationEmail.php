<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReservationConfirmationEmail extends ReservationCommunicationJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public function __construct($Reservation)
    {
        $this->Reservation = $Reservation;
    }

    public function handle()
    {
        $DateTime = \DateTime::createFromFormat('Y-m-d', $this->Reservation->date);

        $mandrill         = new \Mandrill(env('MANDRILL'));
        $template_content = [];
        $message          = [
            'to'                => [
                [
                    'email' => $this->Reservation->email,
                    'name'  => $this->Reservation->first_name . ' ' . $this->Reservation->last_name,
                    'type'  => 'to',
                ],
            ],
            'global_merge_vars' => [
                [
                    'name'    => 'WHO',
                    'content' => $this->Reservation->first_name,
                ],
                [
                    'name'    => 'WHEN',
                    'content' => $DateTime->format('l') . ', ' . $DateTime->format('F') . ' ' . $DateTime->format('j') . ' at ' . Location::toTime($this->Reservation->time),
                ],
                [
                    'name'    => 'WHERE',
                    'content' => $this->Reservation->Location->name . ' | ' . $this->Reservation->Location->address . ', ' . $this->Reservation->Location->city . ', ' . $this->Reservation->Location->region,
                ],
                [
                    'name'    => 'LINK',
                    'content' => $this->Reservation->url,
                ],
            ],
        ];

        $template = 'reservation-confirmation' . $this->getTemplateLanguageSuffix();

        $mandrill->messages->sendTemplate($template, $template_content, $message);
    }
}
