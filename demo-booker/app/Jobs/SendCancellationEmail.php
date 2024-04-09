<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCancellationEmail extends ReservationCommunicationJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public function __construct($Reservation)
    {
        $this->Reservation = $Reservation;
    }

    public function handle()
    {
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
            ],
        ];

        $template = 'system-initiated-reservation-cancelation' . $this->getTemplateLanguageSuffix();

        $mandrill->messages->sendTemplate($template, $template_content, $message);
    }
}
