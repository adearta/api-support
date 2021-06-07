<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationWebinarModel;
use App\Models\StudentParticipantAkbarModel;
use App\Models\StudentModel;
use App\Models\WebinarAkbarModel;
use App\Jobs\SendMailReminderJob;
use App\Models\SchoolParticipantAkbarModel;
use Illuminate\Support\Facades\DB;

class ReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:notif';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the reminder to participants';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $this->reminderStudent(7);
        $this->reminderStudent(1);
    }

    public function reminderStudent($day)
    {
        $tbStudentParticipant = StudentParticipantAkbarModel::tableName();
        $tbSchoolParticipant = SchoolParticipantAkbarModel::tableName();
        $tbStudent = StudentModel::tableName();
        $tbWebinar = WebinarAkbarModel::tableName();
        $tbNotification = NotificationWebinarModel::tableName();

        $event = DB::select("select * from " . $tbWebinar . " as web left join " . $tbSchoolParticipant . " as school on web.id = school.webinar_id left join " . $tbStudentParticipant . " as participant on school.id = participant.school_participant_id where school.status > 2 and web.event_date = current_date + interval '" . $day . " days'");

        if (count($event) > 0) {
            foreach ($event as $e) {
                $student = DB::connection('pgsql2')->table($tbStudent)
                    ->where('id', '=', $e->student_id)
                    ->select('name', 'email')
                    ->get();
                DB::table($tbNotification)->insert(array(
                    'student_id'       => $e->student_id,
                    'webinar_akbar_id' => $e->webinar_id,
                    'message_id'    => "Diingatkan kembali bahwa Webinar dengan judul " . $e->event_name . " akan dilaksakan h-" . $day . " dari sekarang, yaitu pada tanggal " . $e->event_date . " dan pada jam " . $e->event_time,
                    'message_en'    => "Webinar reminder with a title" . $e->event_name . " will be held on " . $e->event_date . " and at " . $e->event_time
                ));

                SendMailReminderJob::dispatch($e, $student, $day);
            }
        }
    }
}
