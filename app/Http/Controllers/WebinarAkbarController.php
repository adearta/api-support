<?php

namespace App\Http\Controllers;

use App\Jobs\EmailInvitationSchoolJob;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolParticipantAkbarModel;
use Illuminate\Http\Request;
use App\Models\WebinarAkbarModel;
use App\Models\StudentParticipantAkbarModel;
use App\Models\StudentModel;
use App\Models\SchoolModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendMailReminderJob;
use App\Models\UserPersonal;
use Symfony\Component\VarDumper\VarDumper;
use App\Models\UserEducationModel;

class WebinarAkbarController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbNotification;
    private $tbSchoolParticipants;
    private $tbStudentParticipants;
    private $tbStudent;
    private $tbSchool;
    private $tbUserPersonal;
    private $tbUserEdu;

    public function __construct()
    {
        $this->tbWebinar = WebinarAkbarModel::tableName();
        $this->tbSchoolParticipants = SchoolParticipantAkbarModel::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
        $this->tbStudentParticipants = StudentParticipantAkbarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbUserPersonal = UserPersonal::tableName();
        $this->tbUserEdu = UserEducationModel::tableName();
    }

    public function getWebinarBySchoolId($id)
    {
        $validation = Validator::make(['id' => $id], [
            'id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            //id -> school_id
            try {
                $data = [];
                $index = 0;
                //menghitung id student yang sudah terdaftar di webinar
                $selectCount = "select count('student.id') from " . $this->tbStudentParticipants . " as student where student.webinar_id = web.id";
                //menampilkan daftar webinar yang dapat diikuti dan status undangan webinar tersebut belum di acc(3) dan reject(2) 
                $webinar = DB::select("select web.id as webinar_id, sch.status, sch.school_id, web.zoom_link, web.event_name, web.event_date, web.event_time, web.event_picture, (500) as quota, (" . $selectCount . ") as registered from " . $this->tbSchoolParticipants . " as sch right join " . $this->tbWebinar . " as web on sch.webinar_id = web.id where sch.school_id = " . $id . " and web.event_date > current_date order by web.id desc");

                foreach ($webinar as $web) {
                    if ($web->status != null) {
                        $school = DB::connection('pgsql2')->table($this->tbSchool)
                            ->where('id', '=', $web->school_id)
                            ->get();

                        $path_zip = null;

                        if ($web->is_certificate) {
                            $path_zip = env("WEBINAR_URL") . $web->certificate;
                        }

                        $data[$index] = (object) array(
                            'webinar_id' => $web->webinar_id,
                            'event_name' => $web->event_name,
                            'event_date' => $web->event_date,
                            'event_time' => $web->event_time,
                            'event_picture' => env("WEBINAR_URL") . $web->event_picture,
                            'zoom_link' => $web->zoom_link,
                            'quota' => $web->quota,
                            'registered' => $web->registered,
                            'school_status' => $web->status,
                            'is_certificate' => $web->is_certificate,
                            'certificate'   => $path_zip,
                            'school' => $school[0]
                        );

                        $index++;
                    }
                }
                return $this->makeJSONResponse($data, 200);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    //bug
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
                    $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.id = " . $webinar_id);

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
    public function statusValue($status)
    {
        switch ($status) {
            case 1:
                return "invited to this webinar";
                break;
            case 2:
                return "already reject this webinar";
                break;
            case 3:
                return "accept this webinar";
                break;
            case 4:
                return "add student to participate to webinar";
                break;
            case 5:
                return "finish this webinar";
                break;
            default:
                return "null";
                break;
        }
    }
    public function detailWebinarSchool(Request $request, $webinar_id)
    {
        $arrayValidation = array(
            'webinar_id' => $webinar_id,
            'school_id'  => $request->school_id
        );
        $validation = Validator::make($arrayValidation, [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'school_id'  => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(["message" => $validation->errors()->first()], 400);
        } else {
            try {
                $data = DB::transaction(function () use ($webinar_id, $request) {
                    // $startYear = array();
                    $condition = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.id = " . $webinar_id . " and school.school_id = " . $request->school_id);
                    if ($condition) {
                        $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.id = " . $webinar_id . " and school.school_id = " . $request->school_id);

                        $school = array();

                        for ($i = 0; $i < count($detail); $i++) {
                            $temp = DB::connection('pgsql2')->table($this->tbSchool)
                                ->where('id', '=', $detail[$i]->school_id)
                                ->get();
                            $school[$i] = $temp[0];
                        }

                        $status = DB::table($this->tbSchoolParticipants)
                            ->where('school_id', '=', $request->school_id)
                            ->where('webinar_id', '=', $webinar_id)
                            ->select('status')
                            ->get();

                        $batch = DB::connection('pgsql2')
                            ->table($this->tbUserEdu)
                            ->where('school_id', '=', $request->school_id)
                            ->select('start_year')
                            ->get();

                        $angkatan = array();
                        for ($i = 0; $i < count($batch); $i++) {
                            $year = DB::connection('pgsql2')
                                ->table($this->tbUserEdu)
                                ->where('school_id', '=', $request->school_id)
                                ->select('start_year')
                                ->get();
                            $angkatan[$i] = $year[$i]->start_year;
                        }
                        $startYear = array_unique($angkatan, SORT_REGULAR);

                        $schParticipant = DB::table($this->tbSchoolParticipants)
                            ->where('school_id', '=', $request->school_id)
                            ->get();

                        $arrWebinar = array();
                        for ($i = 0; $i < count($schParticipant); $i++) {
                            $schParticipant = DB::table($this->tbSchoolParticipants)
                                ->where('school_id', '=', $request->school_id)
                                ->select('webinar_id')
                                ->get();
                            $arrWebinar[$i] = $schParticipant[$i]->webinar_id;
                        }
                        $arrTime = array();
                        // $value = array();
                        for ($i = 0; $i < count($arrWebinar); $i++) {
                            $time = DB::table($this->tbWebinar)->where('id', '=', $arrWebinar[$i])->select('event_date')->get();
                            $arrTime[$i] = $time[0]->event_date;
                            // $valueTime = array_values($arrTime);
                        }
                        $participant = DB::table($this->tbStudentParticipants)->where("webinar_id", "=", $webinar_id)->get();
                        $totalParticipant = count($participant);
                        $availableSlot = 500 - $totalParticipant;
                        // RESPONSE BODY should have property id, event_name, event_picture, event_date, event_time, zoom_link, is_joined, option_date & option_student
                        $angkatanArr = array();
                        $idTemp = 0;
                        foreach ($startYear as $year) {
                            $angkatan[$idTemp] = array(
                                "year"  => $year,
                                "total" => count(DB::connection('pgsql2')->table($this->tbUserEdu)->where('school_id', '=', $request->school_id)->where("start_year", '=', $year)->get())
                            );
                            $angkatanArr[$idTemp] = $angkatan[$idTemp];
                            $idTemp++;
                        }
                        $join = false;
                        $case = $status[0]->status;
                        if ($case >= 3 && $case <= 5) {
                            $join = true;
                            $angkatanArr;
                        } else {
                            $join = false;
                            $angkatanArr = array();
                        }
                        $response = array(
                            "id"                => $webinar_id,
                            "event_name"        => $detail[0]->event_name,
                            "event_picture"     => env("WEBINAR_URL") . $detail[0]->event_picture,
                            "event_date"        => $detail[0]->event_date,
                            "event_time"        => $detail[0]->event_time,
                            "zoom_link"         => $detail[0]->zoom_link,
                            "is_joined"         => $join,
                            "option_student"    => $angkatanArr,
                            "slot_webinar"      => 500,
                            "available_slot"    => $availableSlot,
                            // "participant"           => $totalParticipant,
                            // "option_date"       => count($startYear),
                            // "is_certificate"    => $detail[0]->is_certificate,
                            // "certificate"       => $path_zip,
                        );

                        return $response;
                    } else {
                        $response = array();
                        return $response;
                    }
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

    public function addWebinar(Request $request)
    {
        //validate data
        $validation = Validator::make($request->all(), [
            'zoom_link'        => 'required|url',
            'event_name'       => 'required|string',
            'event_date'       => 'required|date_format:Y-m-d',
            'event_time'       => 'required|date_format:H:i:s',
            'event_picture'    => 'required|mimes:jpg,jpeg,png|max:2000',
            'school_id.*'      => 'required|exists:pgsql2.' . $this->tbSchool . ',id'
        ]);

        if ($validation->fails()) {
            return response()->json(["message" => $validation->errors()->first()], 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $duplicatename = DB::table($this->tbWebinar)
                    ->where("event_name", "=", $request->event_name)
                    ->get();
                if (count($duplicatename) > 0) {
                    $message = "webinar data already exist !";
                    $code = 200;
                    return $this->makeJSONResponse(["message" => $message], $code);
                } else {
                    if ($request->event_date > date("Y-m-d")) {
                        if ($file = $request->file('event_picture')) {
                            try {
                                $path = $file->store('webinar_akbar', 'public');
                                $webinar = array(
                                    'zoom_link'     => $request->zoom_link,
                                    'event_name'    => $request->event_name,
                                    'event_date'    => $request->event_date,
                                    'event_time'    => $request->event_time,
                                    'event_picture' => $path,
                                    // 'is_deleted' => true
                                );

                                $webinarId = DB::table($this->tbWebinar)->insertGetId($webinar);
                                $participantInsert = [];
                                $notifInsert = [];
                                $schoolIdArray = "";

                                for ($i = 0; $i < count($request->school_id); $i++) {
                                    $participantInsert[$i] = array(
                                        'webinar_id'    => $webinarId,
                                        'school_id'     => $request->school_id[$i],
                                    );

                                    $notifInsert[$i] = array(
                                        'school_id'     => $request->school_id[$i],
                                        'webinar_akbar_id' => $webinarId,
                                        'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $request->event_name . " pada tanggal " . $request->event_date . " dan pada jam " . $request->event_time,
                                        'message_en'    => "You get an invitation to join in a webinar with a title" . $request->event_name . " on " . $request->event_date . " and at " . $request->event_time
                                    );
                                }

                                SchoolParticipantAkbarModel::insert($participantInsert);
                                NotificationWebinarModel::insert($notifInsert);

                                $schoolid = SchoolParticipantAkbarModel::select('school_id')
                                    ->where('webinar_id', '=', $webinarId)
                                    ->get();

                                for ($i = 0; $i < count($schoolid); $i++) {
                                    if ($i == 0) {
                                        $schoolIdArray .= $schoolid[$i]->school_id;
                                    } else {
                                        $schoolIdArray .= ", " . $schoolid[$i]->school_id;
                                    }
                                }

                                $schoolAll = DB::connection("pgsql2")->table($this->tbSchool)
                                    ->whereRaw('id = ANY(ARRAY[' . $schoolIdArray . '])')
                                    ->orderBy('name', 'asc')
                                    ->get();

                                foreach ($schoolAll as $sch) {
                                    $schoolToEmail[0] = (object) array(
                                        'name' => $sch->name,
                                        'email' => $sch->email
                                    );

                                    EmailInvitationSchoolJob::dispatch($webinar, $schoolToEmail);
                                }

                                //respon
                                $respon = DB::table($this->tbWebinar)
                                    ->where('id', '=', $webinarId)
                                    ->select('*')
                                    ->get();
                            } catch (Exception $e) {
                                echo $e;
                            }
                            $response = array(
                                "id"                => $webinarId,
                                "event_name"        => $respon[0]->event_name,
                                "event_date"        => $respon[0]->event_date,
                                "event_time"        => $respon[0]->event_time,
                                "event_picture"     => env("WEBINAR_URL") . $respon[0]->event_picture,
                                "schools"           => $schoolAll,
                                "zoom_link"         => $respon[0]->zoom_link,
                                "is_certificate"    => $respon[0]->is_certificate,
                                "certificate"       => $respon[0]->certificate
                            );

                            return $response;
                        }
                    } else {
                        return (object) array(
                            "message" => "The event date must be after today "
                        );
                    }
                }
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed !"], 404);
            }
        }
    }
    //edit webinar
    public function editWebinar($webinar_id, Request $request)
    {
        //validasi 
        //return response($request->zoom_link, 200);
        $arrayValidation = array(
            'webinar_id'    => $webinar_id,
            'zoom_link'     => $request->zoom_link,
            'event_name'    => $request->event_name,
            'event_date'    => $request->event_date,
            'event_picture' => $request->event_picture,
            'school_id'     => $request->school_id
        );

        $validation = Validator::make($arrayValidation, [
            'webinar_id'    => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'zoom_link'     => 'nullable|url',
            'event_name'    => 'nullable|string',
            'event_date'    => 'nullable|date_format:Y-m-d',
            'event_time'    => 'nullable|date_format:H:i:s',
            'event_picture' => 'nullable|mimes:jpg,jpeg,png|max:2000',
            'school_id.*'   => 'nullable|numeric|exists:pgsql2.' . $this->tbSchool . ',id'
        ]);
        if ($validation->fails()) {
            return response()->json(["message" => $validation->errors()->first()], 400);
        } else {
            //find webinar id
            $webinar = WebinarAkbarModel::findOrFail($webinar_id);
            //set modified

            if (!empty($webinar)) {
                $data = DB::transaction(function () use ($request, $webinar) {
                    $path = $webinar->event_picture;
                    if ($file = $request->file('event_picture')) {
                        $path = $file->store('webinar_akbar', 'public');
                    }
                    $datetime = Carbon::now();
                    $datetime->toDateTimeString();
                    $edited = array(
                        'zoom_link' => $this->checkParam($request->zoom_link, $webinar->zoom_link),
                        'event_name' => $this->checkParam($request->event_name, $webinar->event_name),
                        'event_date' => $this->checkParam($request->event_date, $webinar->event_date),
                        'event_time' => $this->checkParam($request->event_time, $webinar->event_time),
                        'modified' => $datetime,
                        'event_picture' => $path
                    );
                    DB::table($this->tbWebinar)
                        ->where('id', '=', $webinar->id)
                        ->update($edited);
                    //respon

                    if ($request->school_id != null) {
                        //delete school participant
                        DB::table($this->tbSchoolParticipants)
                            ->where('webinar_id', '=', $webinar->id)
                            ->delete();

                        //add school participant
                        foreach ($request->school_id as $temp => $value) {
                            $data = DB::table($this->tbSchoolParticipants)
                                ->where('school_id', '=', $value)
                                ->where('webinar_id', '=', $webinar->id)
                                ->get();

                            if (count($data) == 0) {
                                //add school participant
                                DB::table($this->tbSchoolParticipants)->insert(array(
                                    'webinar_id'    => $webinar->id,
                                    'school_id'     => $value,
                                ));

                                //add notif
                                DB::table($this->tbNotification)->insert(array(
                                    'school_id'     => $value,
                                    'webinar_akbar_id' => $webinar->id,
                                    'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $request->event_name . " pada tanggal " . $request->event_date . " dan pada jam " . $request->event_time,
                                    'message_en'    => "You get an invitation to join in a webinar with a title" . $request->event_name . " on " . $request->event_date . " and at " . $request->event_time
                                ));

                                $school = DB::connection("pgsql2")->table($this->tbSchool)
                                    ->where('id', '=', $value)
                                    ->select('name', 'email')
                                    ->get();
                                //send email
                                EmailInvitationSchoolJob::dispatch($edited, $school);
                            }
                        }
                    }

                    $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.id = " . $webinar->id);
                    $schoolId = [];
                    for ($i = 0; $i < count($detail); $i++) {
                        $temp = DB::connection('pgsql2')->table($this->tbSchool)
                            ->where('id', '=', $detail[$i]->school_id)
                            ->select('*')
                            ->get();

                        $schoolId[$i] = $temp[0];
                    }

                    $path_zip = null;

                    if ($detail[0]->is_certificate) {
                        $path_zip = env("WEBINAR_URL") . $detail[0]->cartificate;
                    }
                    $response = array(
                        "id"                => $webinar->id,
                        "event_name"        => $detail[0]->event_name,
                        "event_date"        => $detail[0]->event_date,
                        "event_time"        => $detail[0]->event_time,
                        "event_picture"     => env("WEBINAR_URL") . $detail[0]->event_picture,
                        "schools"           => $schoolId,
                        "zoom_link"         => $request->zoom_link,
                        "is_certificate"    => $detail[0]->is_certificate,
                        "certificate"       => $path_zip,
                    );

                    return array(
                        'response' => $response,
                        'code' => 200
                    );
                });
                if ($data) {
                    $message = $data['response'];
                    $code = $data['code'];
                } else {
                    $message = ["message" => "failed"];
                    $code = 400;
                }
            }
            return $this->makeJSONResponse($message, $code);
        }
    }

    private function checkParam($param, $dbData)
    {
        $data = $param;
        if ($data == null) {
            $data = $dbData;
        }

        return $data;
    }

    public function addSchoolParticipants(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($request) {
                    $success = 0;
                    $message = "";
                    $code = 0;
                    $webinar = DB::table($this->tbWebinar)->where('id', '=', $request->webinar_id)->get();
                    foreach ($request->school_id as $s) {
                        $school = DB::table($this->tbSchoolParticipants)
                            ->where('school_id', '=', $s)
                            ->where('webinar_id', '=', $webinar[0]->id)
                            ->get();

                        if (count($school) == 0) {
                            $success++;
                            DB::table($this->tbSchoolParticipants)->insert(array(
                                'webinar_id'    => $request->webinar_id,
                                'school_id'     => $s,
                            ));

                            DB::table($this->tbNotification)->insert(array(
                                'school_id'     => $s,
                                'webinar_akbar_id' => $webinar[0]->id,
                                'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_time,
                                'message_en'    => "You get an invitation to join in a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_time
                            ));

                            $school = DB::connection("pgsql2")
                                ->table($this->tbSchool)
                                ->select('name', 'email')
                                ->where('id', '=', $s)
                                ->get();

                            $webinarEmail = array(
                                'zoom_link'         => $webinar[0]->zoom_link,
                                'event_name'        => $webinar[0]->event_name,
                                'event_date'        => $webinar[0]->event_date,
                                'event_time'        => $webinar[0]->event_time,
                                'event_picture'     => env("WEBINAR_URL") . $webinar[0]->event_picture
                            );

                            EmailInvitationSchoolJob::dispatch($webinarEmail, $school);
                        } else {
                            if ($success > 0) {
                                $success--;
                            }
                        }
                    }

                    if ($success > 0) {
                        $message = "Success to add " . $success . " from " . count($request->school_id) . " school to this event";
                        $code = 200;
                    } else {
                        $message = "All the school has been registered on this event";
                        $code = 202;
                    }

                    return (object) array(
                        'message' => $message
                    );
                });

                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    //delete webinar
    public function destroyWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(["message" => $validation->errors()->first()], 400);
        } else {
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $webinar_id)
                ->get();
            $delete = WebinarAkbarModel::findOrfail($webinar_id);
            $name = str_replace(' ', '_', $webinar[0]->event_name);
            $path = 'certificate_akbar/webinar_' . $name;
            if ($delete) {
                if (Storage::disk('public')->exists($delete->event_picture)) {
                    Storage::disk('public')->delete($delete->event_picture);
                    Storage::disk('public')->deleteDirectory($path);
                    $delete->delete();

                    return $this->makeJSONResponse(['message' => "Sucessfully delete webinar!"], 200);
                } else {
                    return $this->makeJSONResponse(['message' => "Can't delete data"], 400);
                }
            }
        }
    }

    public function participantList($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($webinar_id) {
                    $participant = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbStudentParticipants . " as participant on participant.webinar_id = web.id where web.id = " . $webinar_id);

                    $response = array();

                    for ($i = 0; $i < count($participant); $i++) {
                        $data = DB::connection('pgsql2')
                            ->table($this->tbUserPersonal, 'user')
                            ->leftJoin($this->tbStudent . " as student", 'user.id', '=', 'student.user_id')
                            ->leftJoin($this->tbSchool . " as school", 'school.id', '=', 'student.school_id')
                            ->where('student.id', '=', $participant[$i]->student_id)
                            ->select('user.first_name', 'user.last_name', 'school.name as school_name')
                            ->get();

                        if (count($data) > 0)
                            $response[$i] = array(
                                "student_name"  => $data[0]->first_name . " " . $data[0]->last_name,
                                "school_name"   => $data[0]->school_name
                            );
                    }

                    return (object) array(
                        'status' => true,
                        'data'   => $response
                    );
                });
                if ($data) {
                    return $this->makeJSONResponse($data->data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
                }
                // });
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    ///bug
    public function listWebinar(Request $request)
    {
        /*
        Param:
        1. Page -> default(0 or null)
        2. Search -> default(null) -> search by webinar event name
        */
        $validation = Validator::make($request->all(), [
            'page'   => 'numeric',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $current_page = 1;
                $data = [];
                $query_pagination = "";
                $query_search = "";
                $start_item = 0;
                $webinar = [];

                $webinar_count = DB::select('select count(id) from ' . $this->tbWebinar);
                $total_page = ceil($webinar_count[0]->count / 10);

                if ($request->page != null && $request->page > 1) {
                    $current_page = intval($request->page);

                    if ($current_page > 1) {
                        $start_item = ($current_page - 1) * 10;
                    }
                }

                if ($current_page <= $total_page) {
                    $query_pagination = " limit 10 offset " . $start_item;

                    if ($request->search != null) {
                        $searchLength = preg_replace('/\s+/', '', $request->search);
                        if (strlen($searchLength) > 0) {
                            $search = strtolower($request->search);
                            $query_search = " where lower(event_name) like '%" . $search . "%'";
                        }
                    }

                    $webinar = DB::select('select * from ' . $this->tbWebinar . $query_search . " order by id desc" . $query_pagination);
                    $listParticipant = [];
                    for ($i = 0; $i < count($webinar); $i++) {
                        $participant = DB::table($this->tbSchoolParticipants)
                            ->where('webinar_id', '=', $webinar[$i]->id)
                            ->get();
                        $listParticipant[$i] = $participant[0];
                        $listSchool = [];
                        $count = count($participant);
                        for ($j = 0; $j < $count; $j++) {
                            // foreach ($listParticipant as $p) {
                            $schools = DB::connection('pgsql2')->table($this->tbSchool)
                                ->where('id', '=', $participant[$j]->school_id)
                                ->get();
                            $listSchool[$j] = $schools[0];
                            // }
                        }
                        // }
                        // for ($s = 0; $s < count($webinar); $s++) {
                        $path_zip = null;

                        if ($webinar[$i]->is_certificate) {
                            $path_zip = env("WEBINAR_URL") . $webinar[$i]->certificate;
                        }
                        $data[$i] = (object) array(
                            'id'                => $webinar[$i]->id,
                            'event_name'        => $webinar[$i]->event_name,
                            'event_date'        => $webinar[$i]->event_date,
                            'event_time'        => $webinar[$i]->event_time,
                            'event_picture'     => env("WEBINAR_URL") . $webinar[$i]->event_picture,
                            // 'list-participant'  => count($participant),
                            // 'part'              => $x,
                            'schools'           => $listSchool,
                            'zoom_link'         => $webinar[$i]->zoom_link,
                            'is_certificate'    => $webinar[$i]->is_certificate,
                            'certificate'       => $path_zip
                        );
                    }
                }

                $response = array(
                    'data' => $data,
                    'pagination' => (object) array(
                        'first_page'    => 1,
                        'last_page'     => $total_page,
                        'current_page'  => $current_page,
                        'current_data'  => count($webinar), // total data based on filter search and page
                        'total_data'    => $webinar_count[0]->count
                    )
                );

                return $response;
            });

            if ($data) {
                return  $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
    public function listWebinarSchool(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $arrWebinar = array();
                $cekSchool = DB::table($this->tbSchoolParticipants)
                    ->where('school_id', '=', $request->school_id)
                    ->get();
                $value = array();
                $listweb = array();
                if ($cekSchool) {
                    $webinarId = DB::table($this->tbSchoolParticipants)
                        ->where('school_id', '=', $request->school_id)
                        ->select('webinar_id')
                        ->get();
                    for ($i = 0; $i < count($webinarId); $i++) {

                        $listId = DB::table($this->tbSchoolParticipants)
                            ->where('school_id', '=', $request->school_id)
                            ->select('webinar_id')
                            ->get();

                        $listweb[$i] = $listId[0];
                        for ($j = 0; $j < count($listweb); $j++)
                            $web = DB::table($this->tbWebinar)
                                ->where('id', '=', $listId[$j]->webinar_id)
                                ->select('id', 'event_picture')
                                ->get();

                        $arrWebinar[$j] =  array(
                            'id'                => $web[0]->id,
                            'event_picture'     => $web[0]->event_picture
                        );
                        $value = array_values($arrWebinar);
                    }
                    return $value;
                } else {
                    return $value;
                }
            });
            if ($data) {
                return  $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
}
