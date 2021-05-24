<?php

namespace App\Jobs;

use App\Mail\SendSchoolMailInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EmailInvitationSchoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    private $webinar;
    private $school;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($webinar, $school)
    {
        $this->webinar = $webinar;
        $this->school = $school;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->school[0]->email)->send(new SendSchoolMailInvitation($this->webinar, $this->school));
    }
}
