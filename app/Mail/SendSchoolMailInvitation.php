<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendSchoolMailInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($webinar, $school)
    {
        $this->webinar = $webinar;
        $this->school = $school;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Undangan untuk mengikuti Webinar ' . $this->webinar['event_name'])
            ->view('email_invitation_school')
            ->with(
                [
                    "webinar" => $this->webinar,
                    "school" => $this->school,
                    "message" => $this
                ]
            );
    }
}
