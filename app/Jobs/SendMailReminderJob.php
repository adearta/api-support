<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailReminder;

class SendMailReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    private $event;
    private $student;
    private $reminder;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event, $student, $reminder)
    {
        $this->event = $event;
        $this->student = $student;
        $this->reminder = $reminder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->student[0]->email)->send(new SendMailReminder($this->event, $this->student, $this->reminder));
    }
}
