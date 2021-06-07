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
use App\Models\UserEducationModel;
use App\Models\WebinarAkbarModel;
use App\Traits\ResponseHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailInvitation;
use App\Jobs\EmailInvitationJob;
use App\Jobs\SendMailReminderJob;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;

class SchoolParticipantAkbarController extends Controller
{
    use ResponseHelper;

    private $tbSchoolParticipant;
    private $tbNotification;
    // private $tbSchool;
    private $tbStudentParticipant;
    private $tbUserEdu;
    private $tbWebinar;
    private $tbStudent;
    private $tbSchool;

    public function __construct()
    {
        $this->tbSchoolParticipant = SchoolParticipantAkbarModel::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
        // $this->tbSchool = SchoolModel::tableName();
        $this->tbStudentParticipant = StudentParticipantAkbarModel::tableName();
        $this->tbUserEdu = UserEducationModel::tableName();
        $this->tbWebinar = WebinarAkbarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
    }

    public function updateSchoolWebinar(Request $request)
    {
        /* post param
            webinar_id
            school_id
            start_year
            // schedule
            status
        */

        /* the status of school
            1 -> created
            2 -> rejected
            3 -> accepted
            4 -> submit the data of student
            5.-> finished webinar
        */

        try {
            $message = "";
            $code = 0;
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $request->webinar_id)
                ->get();
            switch ($request->status) {
                case 2:
                    $validation = Validator::make($request->all(), [
                        'webinar_id' => 'required|numeric',
                        'school_id' => 'required|numeric',
                        'status' => 'required|numeric'
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        DB::table($this->tbSchoolParticipant)
                            ->where('webinar_id', '=', $request->webinar_id)
                            ->where('school_id', '=', $request->school_id)
                            ->update(['status' => $request->status]);
                        $message = "Invitation successfully rejected";
                        $code = 200;
                    }
                    break;

                case 3:
                    $validation = Validator::make($request->all(), [
                        'webinar_id' => 'required|numeric',
                        'school_id' => 'required|numeric',
                        'status' => 'required|numeric'
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        $status = DB::select('select status from ' . $this->tbSchoolParticipant . " where school_id = " . $request->school_id . "and status != 1 and status !=5");
                        if (empty($status)) {
                            $date_to = $webinar[0]->event_date;
                            $to = strtotime($date_to);
                            $date_maximum = date('Y-m-d', strtotime('+3 days'));
                            $event = strtotime($date_maximum);
                            // $date_event = date($event);
                            $now = date("Y-m-d");
                            $interval = $to - $event;
                            // if the interval is less than 4 days
                            if ($interval < 4) {
                                //if the interval just 1 day
                                if ($interval == 1) {
                                    //use today
                                    $now = date("Y-m-d");
                                    //cek status in the database if it's not 1 and and 5
                                    DB::table($this->tbSchoolParticipant)
                                        ->where('webinar_id', '=', $request->webinar_id)
                                        ->where('school_id', '=', $request->school_id)
                                        ->update([
                                            'status' => $request->status,
                                            'schedule' => $now,
                                        ]);
                                    $message = "invitation successfully accepted, please input student maximum today!";
                                    $code = 200;
                                } else {
                                    //pake yg kode dibawah
                                    $cs = 3;
                                    $cs = $cs - 1;
                                    $decrease = strtotime('+' . $cs . ' days');
                                    $date = date("Y-m-d", $decrease);
                                    DB::table($this->tbSchoolParticipant)
                                        ->where('webinar_id', '=', $request->webinar_id)
                                        ->where('school_id', '=', $request->school_id)
                                        ->update([
                                            'status' => $request->status,
                                            'schedule' => $date,
                                        ]);

                                    $message = "Invitation successfully accepted, please input students maximum" . $date . "days from now!";
                                    $code = 200;
                                }
                            } else {
                                DB::table($this->tbSchoolParticipant)
                                    ->where('webinar_id', '=', $request->webinar_id)
                                    ->where('school_id', '=', $request->school_id)
                                    ->update([
                                        'status' => $request->status,
                                        'schedule' => $date_maximum,
                                    ]);

                                $message = "Invitation successfully accepted, input students maximum days from now!";
                                $code = 200;
                            }
                        }
                    }
                    break;
                    //jawaban 2
                case 4:
                    $validation = Validator::make($request->all(), [
                        'webinar_id' => 'required|numeric',
                        'school_id' => 'required|numeric',
                        'start_year' => 'required|numeric',
                        'status' => 'required|numeric'
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        $schoolParticipant = DB::select('select schedule, id from ' . $this->tbSchoolParticipant . " where school_id = " . $request->school_id . " and webinar_id = " . $request->webinar_id);

                        if ($schoolParticipant[0]->schedule >= date("Y-m-d")) {
                            DB::table($this->tbSchoolParticipant)
                                ->where('webinar_id', '=', $request->webinar_id)
                                ->where('school_id', '=', $request->school_id)
                                ->update(['status' => $request->status]);

                            $participant = DB::table($this->tbStudentParticipant)
                                ->where('webinar_id', '=', $request->webinar_id)
                                ->get();
                            //tabel usereducation 
                            $student = DB::connection('pgsql2')->table($this->tbUserEdu)
                                ->where('school_id', '=', $request->school_id)
                                //batch diganti start_year (angkatan)
                                ->where('start_year', '=', $request->start_year)
                                //is_verified diganti verified
                                ->where('verified', '=', true)
                                ->get();

                            $registered = 0;
                            $total = 0;
                            $newparticipants = 0;
                            //count
                            $total = count($participant);
                            //kuota sekarang + jumlah peserta yang akan di daftarkan
                            $newparticipants = count($student);
                            $countparticipants = $total + $newparticipants;
                            //cek apakah melebihi 500 /tidak
                            if ($countparticipants <= 500) {
                                for ($i = 0; $i < $newparticipants; $i++) {

                                    $studentId = DB::connection('pgsql2')->table($this->tbStudent)->where('nim', '=', $student[$i]->nim)->get();

                                    $data = DB::select('select student_id from ' . $this->tbStudentParticipant . " where student_id = " . $studentId[0]->id . " and webinar_id = " . $request->webinar_id);

                                    if (empty($data)) {
                                        $registered++;
                                        DB::table($this->tbStudentParticipant)->insert(array(
                                            'school_participant_id' => $schoolParticipant[0]->id,
                                            'webinar_id'            => $request->webinar_id,
                                            'student_id'            => $studentId[0]->id
                                        ));
                                        DB::table($this->tbNotification)->insert(array(
                                            'student_id'       => $studentId[0]->id,
                                            'webinar_akbar_id' => $request->webinar_id,
                                            'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_time,
                                            'message_en'    => "You get an invitation to join in a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_time
                                        ));
                                        $this->sendMailInvitation($request->webinar_id, $studentId[0]->id);
                                    }
                                }
                                //success add data
                                $message = "Succes add data student";
                                $code = 200;
                                //if more than 500 automatically rejected
                            } else {
                                $message = "gagal";
                                $code = 200;
                            }
                            break;
                        } else {
                            $message = "Cannot registered data of students because you has passed the deadline for registration";
                            $code = 202;
                        }
                    }
                    break;
                case 5:
                    $validation = Validator::make($request->all(), [
                        'webinar_id' => 'required|numeric',
                        'school_id' => 'required|numeric',
                        'status' => 'required|numeric'
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        DB::table($this->tbSchoolParticipant)
                            ->where('webinar_id', '=', $request->webinar_id)
                            ->where('school_id', '=', $request->school_id)
                            ->update(['status' => $request->status]);
                        $message = "webinar has done";
                        $code = 200;
                        break;
                    }
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

    public function sendMailInvitation($webinar_id, $student_id)
    {
        try {
            $webinar = DB::select("select * from " . $this->tbWebinar . " where id = " . $webinar_id);
            $student = DB::connection('pgsql2')->table($this->tbStudent)
                ->where('id', '=', $student_id)
                ->get();
            EmailInvitationJob::dispatch($webinar, $student);

            return $this->makeJSONResponse(['message' => "email terkirim"], 200);
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function listSchool(Request $request)
    {
        //param -> search -> nullable -> search by student name;
        $query_search = "";
        if ($request->search != null) {
            $searchLength = preg_replace('/\s+/', '', $request->search);
            if (strlen($searchLength) > 0) {
                $search = strtolower($request->search);
                $query_search = " where lower(name) like '%" . $search . "%'";
            }
        }

        $response = DB::connection("pgsql2")->select('select * from ' . $this->tbSchool . $query_search);

        return $this->makeJSONResponse($response, 200);
    }
}
