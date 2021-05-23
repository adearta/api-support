<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailInvitation;

class EmailInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    private $webinar;
    private $student;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($webinar, $student)
    {
        $this->webinar = $webinar;
        $this->student = $student;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to("gunk.adi15@gmail.com")->send(new SendMailInvitation($this->webinar, $this->student));
    }
}
