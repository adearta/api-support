<?php

namespace App\Console\Commands;

use App\Jobs\SendMailReminderPaymentJob;
use App\Models\CareerSupportModelsNormalStudentParticipants;
use Illuminate\Console\Command;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\NotificationWebinarModel;
use App\Models\StudentModel;
use Illuminate\Support\Facades\DB;

class ReminderPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Payment Reminder';

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
        $this->paymentReminder(7);
        $this->paymentReminder(1);
        // return 0;
    }
    public function paymentReminder($day)
    {
        $tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $tbStudent = StudentModel::tableName();
        $tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $tbNotification = NotificationWebinarModel::tableName();

        $event = DB::select("select * from " . $tbParticipant . " as participant left join " . $tbWebinar . " as web on web.id = participant.webinar_id where web.event_date = current_date + interval " . $day . " day");

        if (!empty($event)) {
            foreach ($event as $e) {
                //select name and email of student
                $student = DB::connection('pgsql2')->table($tbStudent)
                    ->where('id', '=', $e->student_id)
                    ->select('name', 'email')
                    ->get();
                DB::table($tbNotification)
                    ->insert(array(
                        'student_id' => $e->student_id,
                        'webinar_id' => $e->webinar_id,
                        'message_id'    => "Diingatkan kembali bahwa Webinar dengan judul " . $e->event_name . " akan dilaksakan h-" . $day . " dari sekarang, yaitu pada tanggal " . $e->event_date . " dan pada jam " . $e->event_time . " silahkan untuk menyelesaikan pembayaran anda!",
                        'message_en'    => "Webinar reminder with a title" . $e->event_name . " will be held on " . $e->event_date . " and at " . $e->event_time . " please settle the payment immediately!"
                    ));
                SendMailReminderPaymentJob::dispatch($e, $student, $day);
            }
        }
    }
}
