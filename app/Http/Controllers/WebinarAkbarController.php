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
                        ->get();

                    $school[$i] = $temp[0];
                }

                $response = array(
                    "event_id"   => $webinar_id,
                    "event_name" => $detail[0]->event_name,
                    "event_date" => $detail[0]->event_date,
                    "event_time" => $detail[0]->event_time,
                    "event_picture" => $detail[0]->event_picture,
                    "schools"    => $school,
                    "zoom_link" => $detail[0]->zoom_link,
                    "is_certificate" => false,
                    "certificate" => "link not found",
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
                                'zoom_link' => $request->zoom_link,
                                'event_name' => $request->event_name,
                                'event_date' => $request->event_date,
                                'event_time' => $request->event_time,
                                'event_picture' => $path,
                                // 'is_deleted' => true
                            );

                            $webinarId = DB::table($this->tbWebinar)->insertGetId($webinar);
                            $schoolAll = [];
                            $index = 0;

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
                                    ->get();

                                //respon
                                $respon = DB::table($this->tbWebinar)
                                    ->where('id', '=', $webinarId)
                                    ->select('*')
                                    ->get();

                                $schoolToEmail[] = (object) array(
                                    'name' => $school[0]->name,
                                    'email' => $school[0]->email
                                );

                                $schoolAll[$index] = $school[0];
                                $index++;

                                EmailInvitationSchoolJob::dispatch($webinar, $schoolToEmail);
                            }
                        } catch (Exception $e) {
                            echo $e;
                        }
                        $response = array(
                            "id"   => $webinarId,
                            "event_name" => $respon[0]->event_name,
                            "event_date" => $respon[0]->event_date,
                            "event_time" => $respon[0]->event_time,
                            "event_picture" => $respon[0]->event_picture,
                            "schools"    => $schoolAll,
                            "zoom_link" => $respon[0]->zoom_link,
                            "is_certificate" => false,
                            "certificate" => "link not found"
                        );

                        return $this->makeJSONResponse($response, 200);
                    }
                } else {
                    return $this->makeJSONResponse(["message" => "The event date must be after today "], 202);
                }
            }
        }
    }
    //edit webinar
    public function editWebinar(Request $request)
    {
        //validasi 
        //return response($request->zoom_link, 200);
        $validation = Validator::make($request->all(), [
            'webinar_id' => 'required',
            'zoom_link' => 'required|url',
            'event_name' => 'required',
            'event_date' => 'required',
            'event_time' => 'required',
            'event_picture' => 'mimes:jpg,jpeg,png|max:2000',
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 202);
        } else {
            //find webinar id
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $request->webinar_id)
                ->select('id as webinar_id', 'event_picture as path')
                ->get();
            //set modified

            if (!empty($webinar)) {
                $data = DB::transaction(function () use ($request, $webinar) {
                    $path = $webinar[0]->path;
                    if ($file = $request->file('event_picture')) {
                        $path = $file->store('webinar_akbar', 'public');
                    }
                    $datetime = Carbon::now();
                    $datetime->toDateTimeString();
                    $edited = array(
                        'zoom_link' => $request->zoom_link,
                        'event_name' => $request->event_name,
                        'event_date' => $request->event_date,
                        'event_time' => $request->event_time,
                        'modified' => $datetime,
                        'event_picture' => $path
                    );
                    DB::table($this->tbWebinar)
                        ->where('id', '=', $request->webinar_id)
                        ->update($edited);
                    //respon

                    $cekSchool = DB::table($this->tbSchoolParticipants)
                        ->where('webinar_id', '=', $request->webinar_id)
                        ->get();

                    //delete school participant //masih gabisa delete
                    foreach ($cekSchool as $temp) {
                        echo 'step 1';
                        $deleteStatus = true;
                        foreach ($request->school_id as $schoolNew) {
                            echo 'step 2';
                            if ($temp->school_id == $schoolNew) {
                                echo 'masuk';
                                $deleteStatus = false;
                            }
                        }

                        if ($deleteStatus) {
                            echo 'gass delete';
                            DB::table($this->tbSchoolParticipants)
                                ->where('webinar_id', '=', $request->webinar_id)
                                ->where('school_id', '=', $temp->school_id)
                                ->delete();
                        }
                    }

                    //add school participant
                    foreach ($request->school_id as $temp) {
                        $data = DB::table($this->tbSchoolParticipants)
                            ->where('school_id', '=', $temp)
                            ->get();

                        if (count($data) == 0) {
                            //add school participant
                            DB::table($this->tbSchoolParticipants)->insert(array(
                                'webinar_id'    => $request->webinar_id,
                                'school_id'     => $temp,
                            ));

                            //add notif
                            DB::table($this->tbNotification)->insert(array(
                                'school_id'     => $temp,
                                'webinar_akbar_id' => $request->webinar_id,
                                'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $request->event_name . " pada tanggal " . $request->event_date . " dan pada jam " . $request->event_time,
                                'message_en'    => "You get an invitation to join in a webinar with a title" . $request->event_name . " on " . $request->event_date . " and at " . $request->event_time
                            ));

                            $school = DB::connection("pgsql2")->table($this->tbSchool)
                                ->where('id', '=', $temp)
                                ->select('name', 'email')
                                ->get();
                            //send email
                            EmailInvitationSchoolJob::dispatch($edited, $school);
                        }
                    }

                    $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbSchoolParticipants . " as school on school.webinar_id = web.id where web.id = " . $request->webinar_id);
                    $schoolId = [];
                    for ($i = 0; $i < count($detail); $i++) {
                        $temp = DB::connection('pgsql2')->table($this->tbSchool)
                            ->where('id', '=', $detail[$i]->school_id)
                            ->select('*')
                            ->get();

                        $schoolId[$i] = $temp[0];
                    }

                    $response = array(
                        "id"   => $request->webinar_id,
                        "event_name" => $request->event_name,
                        "event_date" => $request->event_date,
                        "event_time" => $request->event_time,
                        "event_picture" => $detail[0]->event_picture,
                        "schools"    => $schoolId,
                        "zoom_link" => $request->zoom_link,
                        "is_certificate" => false,
                        "certificate" => "link not found",
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
                    $message = "failed";
                    $code = 400;
                }
            }
            return $this->makeJSONResponse($message, $code);
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

        if ($delete) {
            if (Storage::disk('public')->exists($delete->event_picture)) {
                Storage::disk('public')->delete($delete->event_picture);
                $delete->delete();

                return $this->makeJSONResponse(['message' => "Sucessfully delete webinar!"], 200);
            } else {
                return $this->makeJSONResponse(['message' => "Can't delete data"], 400);
            }
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

    public function listWebinar(Request $request)
    {
        /*
        Param:
        1. Page -> default(0 or null)
        2. Search -> default(null) -> search by webinar event name
        */
        $current_page = 1;
        $data = [];
        $query_pagination = "";
        $query_search = "";
        $start_item = 0;

        $webinar_count = DB::select('select count(id) from ' . $this->tbWebinar);
        $total_page = ceil($webinar_count[0]->count / 10);

        if ($request->page == null || $request->page <= 1) {
            $current_page = 1;
        } else {
            $current_page = $request->page;

            if ($current_page > $total_page) {
                $current_page = $total_page;
            }

            if ($current_page > 1) {
                $start_item = ($current_page - 1) * 10;
            }
        }

        $query_pagination = " limit 10 offset " . $start_item;

        if ($request->search != null) {
            $searchLength = preg_replace('/\s+/', '', $request->search);
            if (strlen($searchLength) > 0) {
                $search = strtolower($request->search);
                $query_search = " where lower(event_name) like '%" . $search . "%'";
            }
        }

        $webinar = DB::select('select * from ' . $this->tbWebinar . $query_search . " order by id desc" . $query_pagination);

        for ($i = 0; $i < count($webinar); $i++) {
            $participant = DB::table($this->tbSchoolParticipants)
                ->where('webinar_id', '=', $webinar[$i]->id)
                ->get();

            $listSchool = [];
            for ($j = 0; $j < count($participant); $j++) {
                $school = DB::connection('pgsql2')->table($this->tbSchool)
                    ->where('id', '=', $participant[$j]->school_id)
                    ->get();

                $listSchool[$j] = $school[0];
            }

            $data[$i] = (object) array(
                'id'                => $webinar[$i]->id,
                'event_name'        => $webinar[$i]->event_name,
                'event_date'        => $webinar[$i]->event_date,
                'event_time'        => $webinar[$i]->event_time,
                'event_picture'     => $webinar[$i]->event_picture,
                'schools'           => $listSchool,
                'zoom_link'         => $webinar[$i]->zoom_link,
                'is_certificate'    => false,
                'certificate'       => 'link not found'
            );
        }

        $response = (object) array(
            'data' => $data,
            'pagination' => (object) array(
                'first_page' => 1,
                'last_page' => $total_page,
                'current_page' => $current_page,
                'current_data' => count($webinar), // total data based on filter search and page
                'total_data' => $webinar_count[0]->count
            )
        );

        return $this->makeJSONResponse($response, 200);
    }
}
