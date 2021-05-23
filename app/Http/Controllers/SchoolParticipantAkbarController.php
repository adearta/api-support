<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolParticipantsCandidateModel;
use App\Models\NotificationCandidateModel;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use App\Models\SchoolParticipantAkbarModel;
use App\Models\StudentParticipantAkbarModel;
use App\Models\StudentModel;
use App\Models\WebinarAkbarModel;
use App\Traits\ResponseHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailInvitation;
use App\Jobs\EmailInvitationJob;
use App\Jobs\SendMailReminderJob;
use Exception;

class SchoolParticipantAkbarController extends Controller
{
    use ResponseHelper;

    private $tbSchoolParticipant;
    private $tbNotification;
    private $tbSchool;
    private $tbStudentParticipant;
    private $tbStudent;
    private $tbWebinar;

    public function __construct()
    {
        $this->tbSchoolParticipant = SchoolParticipantAkbarModel::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudentParticipant = StudentParticipantAkbarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbWebinar = WebinarAkbarModel::tableName();
    }

    public function updateSchoolWebinar(Request $request)
    {
        /* post param
            webinar_id
            school_id
            year
            schedule
            status
        */

        /* the status of school
            1 -> created
            2 -> rejected
            3 -> accepted
            4 -> submit the data of student
        */
        try {
            $message = "";
            $code = 0;
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $request->webinar_id)
                ->get();

            switch ($request->status) {
                case 2:
                    DB::table($this->tbSchoolParticipant)
                        ->where('webinar_id', '=', $request->webinar_id)
                        ->where('school_id', '=', $request->school_id)
                        ->update(['status' => $request->status]);

                    $message = "Invitation successfully rejected";
                    $code = 200;
                    break;
                case 3:
                    if ($request->schedule < date("Y-m-d")) {
                        $message = "The date must not be before today";
                        $code = 202;
                    } else if ($request->schedule > $webinar[0]->event_date) {
                        $message = "the date must not be past the date of the event ";
                        $code = 202;
                    } else {
                        DB::table($this->tbSchoolParticipant)
                            ->where('webinar_id', '=', $request->webinar_id)
                            ->where('school_id', '=', $request->school_id)
                            ->update([
                                'status' => $request->status,
                                'schedule' => $request->schedule
                            ]);

                        $message = "Invitation successfully accepted";
                        $code = 200;
                    }
                    break;
                case 4:
                    $schoolSchedule = DB::select('select schedule from ' . $this->tbSchoolParticipant . " where school_id = " . $request->school_id . " and webinar_id = " . $request->webinar_id);

                    if ($schoolSchedule[0]->schedule > date("Y-m-d")) {
                        DB::table($this->tbSchoolParticipant)
                            ->where('webinar_id', '=', $request->webinar_id)
                            ->where('school_id', '=', $request->school_id)
                            ->update(['status' => $request->status]);

                        $participant = DB::table($this->tbStudentParticipant)
                            ->where('webinar_id', '=', $request->webinar_id)
                            ->get();

                        $student = DB::table($this->tbStudent)
                            ->where('school_id', '=', $request->school_id)
                            ->where('year', '=', $request->year)
                            ->where('is_verified', '=', true)
                            ->get();

                        $registered = 0;
                        $total = 0;
                        for ($i = 0; $i < count($student); $i++) {
                            $total = count($participant) + $i + 1;
                            if ($total < 500) {
                                $registered++;
                                DB::table($this->tbStudentParticipant)->insert(array(
                                    'school_id'     => $request->school_id,
                                    'webinar_id'    => $request->webinar_id,
                                    'student_id'    => $student[$i]->id
                                ));

                                DB::table($this->tbNotification)->insert(array(
                                    'student_id'       => $student[$i]->id,
                                    'webinar_akbar_id' => $request->webinar_id,
                                    'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_time,
                                    'message_en'    => "You get an invitation to join in a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_time
                                ));
                            } else {
                                break;
                            }
                        }

                        if ($registered > 0) {
                            $message = "Successfully registered " . $registered . " out of " . count($student) . " students data";
                            $code = 200;
                        } else {
                            $message = "Cannot registered your data of students because the quota is full";
                            $code = 202;
                        }
                    } else {
                        $message = "Cannot registered data of students because you has passed the deadline for registration";
                        $code = 202;
                    }

                    break;
            }

            return $this->makeJSONResponse(['message' => $message], $code);
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function getSchoolData()
    {
        $data = DB::select('select * from career_support_models_school');
        return $this->makeJSONResponse(['data' => $data], 200);
    }
    public function getSchoolParticipants()
    {
        $data = DB::select('select * from career_support_models_schoolparticipants');
        return $this->makeJSONResponse(['data' => $data], 200);
    }

    public function sendMailInvitation(Request $request)
    {
        try {
            $webinar = DB::select("select * from " . $this->tbWebinar . " where id = " . $request->webinar_id);
            $student = DB::select("select * from " . $this->tbStudent . " where id = " . $request->student_id);
            //Mail::to("gunk.adi15@gmail.com")->send(new SendMailInvitation($webinar));
            EmailInvitationJob::dispatchSync($webinar, $student);

            return $this->makeJSONResponse(['message' => "email terkirim"], 200);
        } catch (Exception $e) {
            echo $e;
        }
    }
}
