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
use App\Models\UserPersonal;
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
    private $tbUserPersonal;

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
        $this->tbUserPersonal = UserPersonal::tableName();
    }

    public function updateSchoolWebinar($webinarId, Request $request)
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
        $school_id = $request->school_id;
        $needValidate = array(
            'webinar_id'    => $webinarId,
            'school_id'     => $school_id,
            'status'        => $request->status,
        );

        $validation = Validator::make($needValidate, [
            'webinar_id'    => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'school_id'     => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            'status'        => 'required|numeric'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            try {
                $message = "";
                $code = 0;

                $schParticipantValidation = DB::table($this->tbSchoolParticipant)
                    ->where('webinar_id', '=', $webinarId)
                    ->where('school_id', '=', $school_id)
                    ->get();

                if (count($schParticipantValidation) > 0) {
                    $webinar = DB::table($this->tbWebinar)
                        ->where('id', '=', $webinarId)
                        ->get();
                    switch ($request->status) {
                        case 1:
                            if ($schParticipantValidation[0]->status > 3) {
                                $message = $this->getMessageFromStatus($schParticipantValidation[0]->status);
                                $code = 400;
                            } else {
                                $this->updateStatusParticipant($webinarId, $school_id, $request->status);
                                $message = "The status of school participant has been restored to default";
                                $code = 200;
                            }
                            break;
                        case 2:
                            if ($schParticipantValidation[0]->status > 3) {
                                $message = $this->getMessageFromStatus($schParticipantValidation[0]->status);
                                $code = 200;
                            } else {
                                $this->updateStatusParticipant($webinarId, $school_id, $request->status);
                                $message = "Invitation successfully rejected";
                                $code = 200;
                            }
                            break;
                        case 3:
                            if ($schParticipantValidation[0]->status == 1) {
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
                                        $this->updateStatusAndScheduleParticipant($webinarId, $school_id, $request->status, $now);
                                        $message = "invitation successfully accepted, please input student maximum today!";
                                        $code = 200;
                                    } else {
                                        //pake yg kode dibawah
                                        $cs = 3;
                                        $cs = $cs - 1;
                                        $decrease = strtotime('+' . $cs . ' days');
                                        $date = date("Y-m-d", $decrease);
                                        $this->updateStatusAndScheduleParticipant($webinarId, $school_id, $request->status, $date);
                                        $message = "Invitation successfully accepted, please input students maximum " . $date;
                                        $code = 200;
                                    }
                                } else {
                                    $this->updateStatusAndScheduleParticipant($webinarId, $school_id, $request->status, $date_maximum);
                                    $message = "Invitation successfully accepted, input students maximum " . $date_maximum;
                                    $code = 200;
                                }
                            } else {
                                $message = $this->getMessageFromStatus($schParticipantValidation[0]->status);
                                $code = 400;
                            }
                            break;
                        case 4:
                            $validation = Validator::make($request->all(), [
                                'start_year'    => 'required|numeric|exists:pgsql2.' . $this->tbUserEdu . ',start_year',
                            ]);
                            if ($validation->fails()) {
                                return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
                            } else {
                                if ($schParticipantValidation[0]->status >= 1) {
                                    $schoolParticipant = DB::select('select schedule, id from ' . $this->tbSchoolParticipant . " where school_id = " . $school_id . " and webinar_id = " . $webinarId);

                                    if ($schoolParticipant[0]->schedule >= date("Y-m-d")) {
                                        $this->updateStatusParticipant($webinarId, $school_id, $request->status);

                                        $participant = DB::table($this->tbStudentParticipant)
                                            ->where('webinar_id', '=', $webinarId)
                                            ->get();
                                        //tabel usereducation 
                                        $student = DB::connection('pgsql2')->table($this->tbUserEdu, 'edu')
                                            ->leftJoin($this->tbStudent . ' as student', 'edu.nim', '=', 'student.nim')
                                            ->where('edu.school_id', '=', $school_id)
                                            ->where('edu.start_year', '=', $request->start_year)
                                            ->where('edu.verified', '=', true)
                                            ->where('student.id', '!=', null)
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

                                                $data = DB::select('select student_id from ' . $this->tbStudentParticipant . " where student_id = " . $studentId[0]->id . " and webinar_id = " . $webinarId);

                                                if (empty($data)) {
                                                    $registered++;
                                                    DB::table($this->tbStudentParticipant)->insert(array(
                                                        'school_participant_id' => $schoolParticipant[0]->id,
                                                        'webinar_id'            => $webinarId,
                                                        'student_id'            => $studentId[0]->id
                                                    ));
                                                    DB::table($this->tbNotification)->insert(array(
                                                        'student_id'        => $studentId[0]->id,
                                                        'webinar_akbar_id'  => $webinarId,
                                                        'message_id'        => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_time,
                                                        'message_en'        => "You get an invitation to join in a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_time
                                                    ));
                                                    $this->sendMailInvitation($webinarId, $studentId[0]->id);
                                                }
                                            }
                                            //success add data
                                            $message = "Success add data student";
                                            $code = 200;
                                            //if more than 500 automatically rejected
                                        } else {
                                            $message = "failed";
                                            $code = 400;
                                        }
                                    } else {
                                        $message = "Cannot registered data of students because you has passed the deadline for registration";
                                        $code = 400;
                                    }
                                } else {
                                    $message = $this->getMessageFromStatus($schParticipantValidation[0]->status);
                                    $code = 400;
                                }
                            }
                            break;
                        case 5:
                            if ($schParticipantValidation[0]->status == 4) {
                                $this->updateStatusParticipant($webinarId, $school_id, $request->status);

                                $message = "Webinar has been finished";
                                $code = 200;
                            } else {
                                $message = $this->getMessageFromStatus($schParticipantValidation[0]->status);
                                $code = 400;
                            }
                            break;
                        default:
                            $message = "Invalid status, only accept the status 1 - 5";
                            $code = 400;
                            break;
                    }
                } else {
                    $message = "This school not invited to this webinar";
                    $code = 400;
                }

                return $this->makeJSONResponse(['message' => $message], $code);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    private function updateStatusParticipant($webinar_id, $school_id, $status)
    {
        DB::table($this->tbSchoolParticipant)
            ->where('webinar_id', '=', $webinar_id)
            ->where('school_id', '=', $school_id)
            ->update(['status' => $status]);
    }

    private function updateStatusAndScheduleParticipant($webinar_id, $school_id, $status, $date)
    {
        DB::table($this->tbSchoolParticipant)
            ->where('webinar_id', '=', $webinar_id)
            ->where('school_id', '=', $school_id)
            ->update([
                'status' => $status,
                'schedule' => $date,
            ]);
    }

    private function getMessageFromStatus($status)
    {
        $message = "";
        switch ($status) {
            case 2:
                $message = "This school has been rejecting this webinar";
                break;
            case 3:
                $message = "This school has been accepting the invitation from this webinar";
                break;
            case 4:
                $message = "This school has registered its students";
                break;
            case 5:
                $message = "This school has completed this webinar";
                break;
        }

        return $message;
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
            $student = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                ->leftJoin($this->tbUserPersonal . ' as user', 'student.user_id', '=', 'user.id')
                ->where('student.id', '=', $student_id)
                ->select('student.id', 'student.phone', 'student.nim', 'student.address', 'student.date_of_birth', 'student.gender', 'student.marital_status', 'student.religion', 'student.employment_status', 'student.description', 'student.avatar', 'student.domicile_id', 'student.user_id as student_user_id', 'student.school_id', 'user.first_name', 'user.last_name', 'user.email')
                ->get();

            EmailInvitationJob::dispatch($webinar, $student);
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function listSchool(Request $request)
    {
        //param -> search -> nullable -> search by school name;
        $search = "";
        if ($request->search != null) {
            $searchLength = preg_replace('/\s+/', '', $request->search);
            if (strlen($searchLength) > 0) {
                $search = strtolower($request->search);
            }
        }

        $response = DB::connection('pgsql2')->table($this->tbSchool)
            ->whereRaw("lower(name) like '%" . $search . "%'")
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get();
        return $this->makeJSONResponse($response, 200);
    }

    public function detailWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(["message" => $validation->errors()->first()], 400);
        } else {
            try {
                $data = DB::transaction(function () use ($webinar_id) {
                    $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipant . " as school on school.webinar_id = web.id where web.id = " . $webinar_id);

                    $school = array();

                    for ($i = 0; $i < count($detail); $i++) {
                        $temp = DB::connection('pgsql2')->table($this->tbSchool)
                            ->where('id', '=', $detail[$i]->school_id)
                            ->get();
                        $school[$i] = $temp[0];
                    }

                    $path_zip = null;

                    if ($detail[0]->is_certificate) {
                        $path_zip = env("WEBINAR_URL") . $detail[0]->certificate;
                    }

                    $candidateCount = StudentParticipantAkbarModel::where('webinar_id', '==', $webinar_id)->count();

                    $response = array(
                        "id"          => $webinar_id,
                        "event_name"        => $detail[0]->event_name,
                        "event_date"        => $detail[0]->event_date,
                        "event_time"        => $detail[0]->event_time,
                        "event_picture"     => env("WEBINAR_URL") . $detail[0]->event_picture,
                        "schools"           => $school,
                        "zoom_link"         => $detail[0]->zoom_link,
                        "is_certificate"    => $detail[0]->is_certificate,
                        "certificate"       => $path_zip,
                        "candidate"         => $candidateCount
                    );

                    return $response;
                });
                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "failed!"], 400);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
}
