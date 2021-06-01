<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Mail\SendCertificate;

class CertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 120;
    private $webinar;
    private $participant;
    private $certificate;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($webinar, $participant, $certificate)
    {
        //
        $this->certificate = $certificate;
        $this->webinar = $webinar;
        $this->participant = $participant;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Mail::to($this->participant[0]->email)->send(new SendCertificate($this->webinar, $this->participant, $this->certificate));
    }
}
