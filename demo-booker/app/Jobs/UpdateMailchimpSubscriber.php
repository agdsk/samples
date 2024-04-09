<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMailchimpSubscriber extends ReservationCommunicationJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public function __construct($Reservation)
    {
        $this->Reservation = $Reservation;
    }

    public function handle()
    {
        // Don't update this user if they are not subscribed
        if ($this->Reservation->subscribed != 1) {
            return;
        }

        $Mailchimp = new \Mailchimp(env('MAILCHIMP'));

        $mc_email      = ['email' => $this->Reservation->email];
        $mc_merge_vars = [
            'FNAME'       => $this->Reservation->first_name,
            'LNAME'       => $this->Reservation->last_name,
            'LOC_ID'      => $this->Reservation->Location->id,
            'LOC_NAME'    => $this->Reservation->Location->name,
            'LOC_BRAND'   => $this->Reservation->Location->Brand->name,
            'LOC_CITY'    => $this->Reservation->Location->city,
            'LOC_ADDRES'  => $this->Reservation->Location->address . ' ' . $this->Reservation->Location->address2,
            'LOC_REGION'  => $this->Reservation->Location->region,
            'LOC_COUNTR'  => $this->Reservation->Location->country,
            'LOC_LANG'    => $this->Reservation->Location->language,
            'LOC_TYPE'    => $this->Reservation->Location->type,
            'DEMO_DATE'   => $this->Reservation->date,
            'DEMO_TIME'   => $this->Reservation->time,
            'DEMO_TIME2'  => Location::toTime($this->Reservation->time),
            'DEMO_STAGE'  => $this->Reservation->status_slug,
            'SOURCE'      => $this->Reservation->source,
            'URL'         => $this->Reservation->url,
        ];

        $mc_double_optin    = false;
        $mc_update_existing = true;

        try {
            $Mailchimp->lists->subscribe('d9bef606b1', $mc_email, $mc_merge_vars, 'html', $mc_double_optin, $mc_update_existing);
        } catch (\Mailchimp_List_InvalidBounceMember $e) {
            //
        } catch (\Mailchimp_Error $e) {
            //
        }
    }
}
