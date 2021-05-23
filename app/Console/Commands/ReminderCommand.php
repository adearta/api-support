<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationWebinarModel;
use App\Models\StudentParticipantAkbarModel;
use App\Models\StudentModel;
use App\Models\WebinarAkbarModel;
use App\Jobs\SendMailReminderJob;
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
        $tbStudent = StudentModel::tableName();
        $tbWebinar = WebinarAkbarModel::tableName();
        $tbNotification = NotificationWebinarModel::tableName();

        $student = DB::select("select * from " . $tbStudentParticipant . " as participant left join " . $tbWebinar . " as web on web.id = participant.webinar_id left join " . $tbStudent . " as student on student.id = participant.student_id where web.event_date = current_date + interval '" . $day . "' day");

        if (!empty($student)) {
            foreach ($student as $s) {
                DB::table($tbNotification)->insert(array(
                    'student_id'       => $s->student_id,
                    'webinar_akbar_id' => $s->webinar_id,
                    'message_id'    => "Diingatkan kembali bahwa Webinar dengan judul " . $s->event_name . " akan dilaksakan h-" . $day . " dari sekarang, yaitu pada tanggal " . $s->event_date . " dan pada jam " . $s->event_time,
                    'message_en'    => "Webinar reminder with a title" . $s->event_name . " will be held on " . $s->event_date . " and at " . $s->event_time
                ));

                SendMailReminderJob::dispatchSync($student, $day);
            }
        }
    }
}
