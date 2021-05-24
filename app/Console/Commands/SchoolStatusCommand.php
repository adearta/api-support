<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\WebinarAkbarModel;
use App\Models\SchoolParticipantAkbarModel;

class SchoolStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the status of school participants';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $tbWebinar;
    private $tbSchoolParticipants;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->tbSchoolParticipants = SchoolParticipantAkbarModel::tableName();
        $this->tbWebinar = WebinarAkbarModel::tableName();
        $this->updateDeadline();
        $this->updateFinish();
    }

    private function updateFinish()
    {;

        $webinar = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.event_date < current_date and school.status != 5");

        if (!empty($webinar)) {
            foreach ($webinar as $web) {
                DB::table($this->tbSchoolParticipants)
                    ->where('webinar_id', '=', $web->webinar_id)
                    ->where('school_id', '=', $web->school_id)
                    ->update([
                        'status' => 5
                    ]);
            }
        }
    }

    public function updateDeadline()
    {
        $school = DB::select("select * from " . $this->tbSchoolParticipants . " where schedule < current_date and status = 3");

        if (!empty($school)) {
            foreach ($school as $s) {
                DB::table($this->tbSchoolParticipants)
                    ->where('webinar_id', '=', $s->webinar_id)
                    ->where('school_id', '=', $s->school_id)
                    ->update([
                        'status' => 6
                    ]);
            }
        }
    }
}
