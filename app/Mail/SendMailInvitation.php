<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($webinar, $student)
    {
        $this->webinar = $webinar;
        $this->student = $student;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Undangan untuk mengikuti Webinar ' . $this->webinar[0]->event_name)
            ->view('email_invitation')
            ->with(
                [
                    "webinar" => $this->webinar,
                    "student" => $this->student,
                    "message" => $this
                ]
            );
    }
}
