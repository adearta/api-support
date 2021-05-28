<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($event, $student, $reminder)
    {
        //
        $this->event = $event;
        $this->student = $student;
        $this->reminder = $reminder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('pengingat pembayaran Webinar' . $this->event->event_name)
            ->view('email_reminder_pembayaran')
            ->with(
                [
                    "event" => $this->event,
                    "student" => $this->student,
                    "reminder" => $this->reminder,
                    "message" => $this
                ]
            );
    }
}
