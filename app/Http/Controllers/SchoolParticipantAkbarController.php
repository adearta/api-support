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
use DateTime;
use Exception;
use Illuminate\Support\Facades\Date;

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
            batch
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
                    DB::table($this->tbSchoolParticipant)
                        ->where('webinar_id', '=', $request->webinar_id)
                        ->where('school_id', '=', $request->school_id)
                        ->update(['status' => $request->status]);
                    $message = "Invitation successfully rejected";
                    $code = 200;
                    break;
                    //jawaban no 1 dan 3
                case 3:
                    $status = DB::select('select status from ' . $this->tbSchoolParticipant . " where school_id = " . $request->school_id . "and status != 1 and status !=5");
                    if (empty($status)) {
                        $date_to = $webinar[0]->event_date;
                        $event = new DateTime($date_to);
                        // $days = strtotime('+3 days');
                        // $maximum_more = date("Y-m-d", $days);
                        // $date = new DateTime($maximum_more);
                        $now = new DateTime('now');
                        $now->modify('+3 days');
                        // $date_time = $date->diff($now);
                        $interval = date_diff($event, $now);
                        // if the interval is less than 4 days
                        if ($interval->format("%a") < 4) {
                            //if the interval just 1 day
                            if ($interval->format("%a") == 1) {
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
                                // $interval_now = date_diff($date_to,date_create($date));
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
                                    'schedule' => $now,
                                ]);

                            $message = "Invitation successfully accepted, input students maximum days from now!";
                            $code = 200;
                        }
                    }
                    break;
                    //jawaban 2
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
                            ->where('batch', '=', $request->batch)
                            ->where('is_verified', '=', true)
                            ->get();

                        $registered = 0;
                        $total = 0;
                        $newparticipants = 0;
                        // $countparticipants =0
                        //new
                        // $count = DB::select('select count()')
                        //count
                        //hitung jumlah peserta sekarang
                        $total = count($participant);
                        //kuota sekarang + jumlah peserta yang akan di daftarkan
                        $newparticipants = count($student);
                        $countparticipants = $total + $newparticipants;
                        //cek apakah melebihi 500 /tidak
                        if ($countparticipants <= 500) {
                            for ($i = 0; $i < $newparticipants; $i++) {
                                $data = DB::select('select student_id from ' . $this->tbStudentParticipant . " where student_id = " . $student[$i]->id . " and webinar_id = " . $request->webinar_id);
                                if (empty($data)) {
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
                                }
                            }
                            //success add data
                            $message = "Succes add data student";
                            $code = 200;
                            //if more than 500 automaticcly rejected
                        } else {
                            $message = "gagal";
                            $code = 200;
                        }
                        break;
                        // if ($registered > 0) {
                        //     $message = "Successfully registered " . $registered . " out of " . count($student) . " students data";
                        //     $code = 200;
                        // } else {
                        //     $message = "Cannot registered your data of students because the quota is full";
                        //     $code = 202;
                        // }
                    } else {
                        $message = "Cannot registered data of students because you has passed the deadline for registration";
                        $code = 202;
                    }

                    break;
                case 5:
                    DB::table($this->tbSchoolParticipant)
                        ->where('webinar_id', '=', $request->webinar_id)
                        ->where('school_id', '=', $request->school_id)
                        ->update(['status' => $request->status]);
                    $message = "webinar has done";
                    $code = 200;
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
}
