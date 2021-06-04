<?php

namespace App\Http\Controllers;

use App\Jobs\EmailInvitationSchoolJob;
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


class WebinarAkbarController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbNotification;
    private $tbSchoolParticipants;
    private $tbStudentParticipants;
    private $tbStudent;
    private $tbSchool;

    public function __construct()
    {
        $this->tbWebinar = WebinarAkbarModel::tableName();
        $this->tbSchoolParticipants = SchoolParticipantAkbarModel::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
        $this->tbStudentParticipants = StudentParticipantAkbarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
    }

    public function getWebinarBySchoolId($id)
    {
        $validation = Validator::make(['id' => $id], [
            'id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            //id -> school_id
            try {
                //menghitung id student yang sudah terdaftar di webinar
                $selectCount = "select count('student.id') from " . $this->tbStudentParticipants . " as student where student.webinar_id = web.id";
                //menampilkan daftar webinar yang dapat diikuti dan status undangan webinar tersebut belum di acc(3) dan reject(2) 
                $webinar = DB::select("select web.id as webinar_id, sch.status, web.zoom_link, web.event_name, web.event_date, web.event_time, web.event_picture, (500) as quota, (" . $selectCount . ") as registered from " . $this->tbSchoolParticipants . " as sch right join " . $this->tbWebinar . " as web on sch.webinar_id = web.id where sch.school_id = " . $id . " and web.event_date > current_date and sch.status != 3 and sch.status !=2 order by web.id desc");

                return $this->makeJSONResponse($webinar, 200);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    public function detailWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.id = " . $webinar_id);

                $school = array();

                for ($i = 0; $i < count($detail); $i++) {
                    $temp = DB::connection('pgsql2')->table($this->tbSchool)
                        ->where('id', '=', $detail[$i]->school_id)
                        ->select('name')
                        ->get();

                    $school[$i] = array(
                        "id"  => $detail[$i]->school_id,
                        "name" => $temp[0]->name,
                        "status" => $detail[$i]->status
                    );
                }

                $response = array(
                    "event_id"   => $webinar_id,
                    "event_name" => $detail[0]->event_name,
                    "event_date" => $detail[0]->event_date,
                    "event_time" => $detail[0]->event_time,
                    "event_picture" => $detail[0]->event_picture,
                    "school"    => $school
                );

                return $this->makeJSONResponse($response, 200);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    public function addWebinar(Request $request)
    {
        //validate data
        $validation = Validator::make($request->all(), [
            'zoom_link' => 'required|url',
            'event_name' => 'required',
            'event_date' => 'required',
            'event_time' => 'required',
            'event_picture' => 'required|mimes:jpg,jpeg,png|max:2000',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->errors(), 202);
        } else {
            $duplicatename = DB::table($this->tbWebinar)
                ->where("event_name", "=", $request->event_name)
                ->get();
            $samedaytime = DB::table($this->tbWebinar)
                ->where("event_date", "=", $request->event_date)
                ->where("event_date", "=", $request->event_date)
                ->where("event_time", "=", $request->event_time)
                ->get();
            if (count($duplicatename) > 0) {
                $message = "webinar data already exist !";
                $code = 200;
                return $this->makeJSONResponse(["message" => $message], $code);
            } else if (count($samedaytime) > 0) {
                $message = "same webinar date and time already exist,please select another day or time!";
                $code = 200;
                return $this->makeJSONResponse(["message" => $message], $code);
            } else {
                if ($request->event_date > date("Y-m-d")) {
                    if ($file = $request->file('event_picture')) {
                        try {
                            $path = $file->store('webinar', 'uploads');
                            $webinar = array(
                                'zoom_link' => $request->zoom_link,
                                'event_name' => $request->event_name,
                                'event_date' => $request->event_date,
                                'event_time' => $request->event_time,
                                'event_picture' => $path,
                                // 'is_deleted' => true
                            );

                            $webinarId = DB::table($this->tbWebinar)->insertGetId($webinar);

                            foreach ($request->school_id as $s) {
                                DB::table($this->tbSchoolParticipants)->insert(array(
                                    'webinar_id'    => $webinarId,
                                    'school_id'     => $s,
                                ));

                                DB::table($this->tbNotification)->insert(array(
                                    'school_id'     => $s,
                                    'webinar_akbar_id' => $webinarId,
                                    'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $request->event_name . " pada tanggal " . $request->event_date . " dan pada jam " . $request->event_time,
                                    'message_en'    => "You get an invitation to join in a webinar with a title" . $request->event_name . " on " . $request->event_date . " and at " . $request->event_time
                                ));

                                $school = DB::connection("pgsql2")->table($this->tbSchool)
                                    ->where('id', '=', $s)
                                    ->select('name', 'email')
                                    ->get();

                                EmailInvitationSchoolJob::dispatch($webinar, $school);
                            }
                        } catch (Exception $e) {
                            echo $e;
                        }

                        return $this->makeJSONResponse(["message" => "Success to save data to database"], 200);
                    }
                } else {
                    return $this->makeJSONResponse(["message" => "The event date must be after today "], 202);
                }
            }
        }
    }
    //edit webinar
    public function editWebinar(Request $request, $webinar_id)
    {
        //validasi 
        $validation = Validator::make($request->all(), [
            'zoom_link' => 'required|url',
            'event_name' => 'required',
            'event_date' => 'required',
            'event_time' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 202);
        } else {
            //find webinar id
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $webinar_id)
                ->select('id as webinar_id')
                ->get();
            //set modified
            $datetime = Carbon::now();
            $datetime->toDateTimeString();

            if (!empty($webinar)) {

                $edited = array(
                    'zoom_link' => $request->zoom_link,
                    'event_name' => $request->event_name,
                    'event_date' => $request->event_date,
                    'event_time' => $request->event_time,
                    'modified' => $datetime
                );
                DB::table($this->tbWebinar)
                    ->where('id', '=', $webinar_id)
                    ->update($edited);

                $message = "webinar data sucessfully updated!";
                $code = 200;
                return response()->json(["message" => $message], $code);
            }
        }
    }
    public function addSchoolParticipants(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric',
            'webinar_id' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
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
                            'zoom_link' => $webinar[0]->zoom_link,
                            'event_name' => $webinar[0]->event_name,
                            'event_date' => $webinar[0]->event_date,
                            'event_time' => $webinar[0]->event_time,
                            'event_picture' => $webinar[0]->event_picture
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

                return $this->makeJSONResponse(['message' => $message], $code);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    //delete webinar
    public function destroyWebinar($webinar_id)
    {
        $delete = WebinarAkbarModel::findOrfail($webinar_id);
        $check = DB::table($this->tbWebinar)
            ->where('id', '=', $webinar_id)
            ->where('is_deleted', '=', true)
            ->select('id as id_webinar')
            ->get();

        if (!empty($check[0])) {
            $delete->delete($check);
            $message_ok = "deleted!";
            return $this->makeJSONResponse($message_ok, 200);
        } else {
            $message_err = "cant find data!";
            return $this->makeJSONResponse($message_err, 400);
        }
    }

    public function participantList($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $participant = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbStudentParticipants . " as participant on participant.webinar_id = web.id where web.id = " . $webinar_id);

                $response = array();

                for ($i = 0; $i < count($participant); $i++) {
                    $data = DB::connection('pgsql2')
                        ->table($this->tbStudent . " as student")
                        ->leftJoin($this->tbSchool . " as school", 'school.id', '=', 'student.school_id')
                        ->where('student.id', '=', $participant[$i]->student_id)
                        ->select('student.name as student_name', 'school.name as school_name')
                        ->get();
                    $response[$i] = array(
                        "student_name"  => $data[0]->student_name,
                        "school_name"   => $data[0]->school_name
                    );
                }

                return $this->makeJSONResponse($response, 200);
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
}
