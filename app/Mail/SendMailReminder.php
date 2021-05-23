<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $reminder)
    {
        $this->data = $data;
        $this->reminder = $reminder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Pengingat untuk Webinar ' . $this->data[0]->event_name)
            ->view('email_reminder')
            ->with(
                [
                    "data" => $this->data,
                    "reminder" => $this->reminder,
                    "message" => $this
                ]
            );
    }
}
