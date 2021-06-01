<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCertificate extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($webinar, $participant, $certificate)
    {
        //
        $this->webinar = $webinar;
        $this->participant = $participant;
        $this->certificate = $certificate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Webinar Certificate ' . $this->webinar[0]->event_name)
            ->view('email_certificate')
            ->with([
                "webinar" => $this->webinar,
                "participant" => $this->participant,
                "certificate" => $this->certificate,
                "message" => $this
            ]);
    }
}
