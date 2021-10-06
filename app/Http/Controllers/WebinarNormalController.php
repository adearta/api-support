<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\StudentModel;
use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use App\Models\UserPersonal;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class WebinarNormalController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbParticipant;
    private $tbNotif;
    private $tbStudent;
    private $tbOrder;
    private $tbSchool;
    private $tbUserPersonal;


    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbUserPersonal = UserPersonal::tableName();
    }



    public function listNormalWebinar()
    {
        try {
            $count = "select count('part.webinar_id') from " . $this->tbParticipant . " as part where part.webinar_id = web.id";
            $datas = DB::select("select web.id as webinar_id, web.event_name, web.event_picture, web.event_date, web.event_start, web.event_end, web.price, (500) as quota, (" . $count . ") as registered from " . $this->tbWebinar . " as web where web.event_date > current_date");

            return $this->makeJSONResponse($datas, 200);
        } catch (Exception $e) {
            echo $e;
        }
    }

    //get the detail of webinar
    public function detailNormalWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            if ($webinar_id == null) {
                return $this->makeJSONResponse(["message" => "webinar must not empty!"], 400);
            }
            try {
                $data = DB::transaction(function () use ($webinar_id) {
                    $webinar = DB::table($this->tbWebinar)
                        ->where('id', '=', $webinar_id)
                        ->get();
                    $participant = DB::table($this->tbParticipant, 'participant')
                        ->leftJoin($this->tbOrder . ' as order', 'participant.id', '=', 'order.participant_id')
                        ->where('order.webinar_id', '=', $webinar_id)
                        ->where('order.status', '=', 'success')
                        ->select('participant.student_id', 'order.id')
                        ->get();

                    $dataStudent = [];
                    $dataSchool = [];
                    for ($i = 0; $i < count($participant); $i++) {
                        $school = DB::connection('pgsql2')->table($this->tbStudent, "student")
                            ->leftJoin($this->tbSchool . " as school", "student.school_id", "=", "school.id")
                            ->where('student.id', '=', $participant[$i]->student_id)
                            ->select("school_id")
                            ->get();
                        $dataSchool[$i] = $school[0];
                    }
                    for ($i = 0; $i < count($dataSchool); $i++) {
                        $temp = DB::connection('pgsql2')->table($this->tbSchool)
                            ->where('id', '=', $dataSchool[$i]->school_id)
                            ->select('*')
                            ->get();

                        $dataStudent[$i] = $temp[0];
                    }

                    $unique = array_values(array_unique($dataStudent, SORT_REGULAR));
                    $path_zip = null;

                    if ($webinar[0]->is_certificate) {
                        $path_zip = env("WEBINAR_URL") . $webinar[0]->certificate;
                    }
                    // Response body harus ada property/field id, event_name, event_picture, event_date, event_price, event_link, is_joined, is_paid & order_id
                    if (count($webinar) > 0) {
                        $responsea = array(
                            "id"                => $webinar_id,
                            "event_name"        => $webinar[0]->event_name,
                            "event_picture"     => env("WEBINAR_URL") . $webinar[0]->event_picture,
                            "event_date"        => $webinar[0]->event_date,
                            // "event_price"       => $webinar[0]->price,
                            "event_link"        => $webinar[0]->event_link,
                            // "is_joined"         => "not join",
                            // "is_paid"           => "not paid",
                            // "order_id"          => $participant[0]->id,
                            "event_start"       => $webinar[0]->event_start,
                            "event_end"         => $webinar[0]->event_end,
                            "schools"           => $unique,
                            "is_certificate"    => false,
                            "certificate"       => $path_zip,
                        );

                        return $responsea;
                    } else {
                        return (object) array(
                            'message' => 'Data not found'
                        );
                    }
                });
                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transaction failed"], 400);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    //for candidate
    public function detailNormalWebinarCandidate(Request $request, $webinar_id)
    {
        $needvalidation = array(
            'webinar_id'    => $webinar_id,
            'student_id'    => $request->student_id
        );
        $validation = Validator::make($needvalidation, [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            if ($webinar_id == null) {
                return $this->makeJSONResponse(["message" => "webinar must not empty!"], 400);
            }
            try {
                $data = DB::transaction(function () use ($webinar_id, $request) {
                    $webinar = DB::table($this->tbWebinar)
                        ->where('id', '=', $webinar_id)
                        ->get();
                    $participant = DB::table($this->tbParticipant, 'participant')
                        ->leftJoin($this->tbOrder . ' as order', 'participant.id', '=', 'order.participant_id')
                        ->where('order.webinar_id', '=', $webinar_id)
                        ->where('participant.student_id', '=', $request->student_id)
                        // ->where('order.status', '=', 'success')
                        ->select('participant.student_id', 'order.id', 'order.status')
                        ->get();
                    if (count($participant) > 0 && $participant != null) {
                        $dataStudent = [];
                        $dataSchool = [];
                        for ($i = 0; $i < count($participant); $i++) {
                            $school = DB::connection('pgsql2')->table($this->tbStudent, "student")
                                ->leftJoin($this->tbSchool . " as school", "student.school_id", "=", "school.id")
                                ->where('student.id', '=', $participant[$i]->student_id)
                                ->select("school_id")
                                ->get();
                            $dataSchool[$i] = $school[0];
                        }

                        if ($webinar[0]->is_certificate) {
                            $path_zip = env("WEBINAR_URL") . $webinar[0]->certificate;
                        }
                        $join = false;
                        $paid = false;
                        $orderId = null;
                        if ($participant[0]->status == "pending" || $participant[0]->status == "success") {
                            $join = true;
                            $paid = true;
                            $orderId = $participant[0]->id;
                        } else {
                            $join;
                            $paid;
                            $orderId;
                        }

                        // Response body harus ada property/field id, event_name, event_picture, event_date, event_price, event_link, is_joined, is_paid & order_id

                        $responsea = array(
                            "id"                => $webinar_id,
                            "event_name"        => $webinar[0]->event_name,
                            "event_picture"     => env("WEBINAR_URL") . $webinar[0]->event_picture,
                            "event_date"        => $webinar[0]->event_date,
                            "event_time"        => $webinar[0]->event_start,
                            "event_price"       => $webinar[0]->price,
                            "event_link"        => $webinar[0]->event_link,
                            "is_joined"         => $join,
                            "is_paid"           => $paid,
                            "order_id"          => $orderId,
                            // "status"            => $participant[0]->status
                        );

                        return $responsea;
                    } else {
                        $join = false;
                        $is_paid = false;
                        $orderId = null;
                        $responsea = array(
                            "id"                => $webinar_id,
                            "event_name"        => $webinar[0]->event_name,
                            "event_picture"     => env("WEBINAR_URL") . $webinar[0]->event_picture,
                            "event_date"        => $webinar[0]->event_date,
                            "event_time"        => $webinar[0]->event_start,
                            "event_price"       => $webinar[0]->price,
                            "event_link"        => $webinar[0]->event_link,
                            "is_joined"         => $join,
                            "is_paid"           => $is_paid,
                            "order_id"          => $orderId,
                        );
                        return $responsea;
                    }
                });
                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transaction failed"], 400);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }


    //get the detail of webinar with the participant list
    public function detailNormalWebinarWithStudent($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($webinar_id) {
                    $webinar = DB::table($this->tbWebinar)
                        ->where('id', '=', $webinar_id)
                        ->get();
                    // $img = CareerSupportModelsWebinarBiasa::find($webinar_id);
                    // echo $img;
                    $registered = DB::table($this->tbWebinar, 'webinar')
                        ->leftJoin($this->tbParticipant . ' as participant', 'webinar.id', '=', 'participant.webinar_id')
                        ->leftJoin($this->tbOrder . ' as pesan', 'participant.id', '=', 'pesan.participant_id')
                        ->where('webinar.id', '=', $webinar_id)
                        ->where('pesan.status', '=', 'success')
                        ->orWhere('pesan.status', '=', 'pending')
                        ->select('participant.student_id', 'pesan.status')
                        ->get();

                    if (count($webinar) > 0) {
                        $student = array();
                        //perlu ditambah nama
                        if (count($registered) > 0) {
                            for ($i = 0; $i < count($registered); $i++) {
                                $temp = DB::connection('pgsql2')->table($this->tbUserPersonal, 'user')
                                    ->leftJoin($this->tbStudent . ' as student', 'user.id', '=', 'student.user_id')
                                    // ->leftJoin($this->tbSchool . ' as school', 'student.school_id', '=', 'school.id')
                                    ->where('student.id', '=', $registered[$i]->student_id)
                                    ->select('student.school_name', 'user.first_name', 'user.last_name')
                                    ->get();

                                $student[$i] = array(
                                    "student_id"  => $registered[$i]->student_id,
                                    "student_name" => $temp[0]->first_name . " " . $temp[0]->last_name,
                                    "school_name" => $temp[0]->school_name,
                                    "participant_status" => $registered[$i]->status
                                );
                            }
                        }
                        $path_zip = null;

                        if ($webinar[0]->is_certificate) {
                            $path_zip = env("WEBINAR_URL") . $webinar[0]->certificate;
                        }
                        $response = array(
                            "id"      => $webinar_id,
                            "event_name"    => $webinar[0]->event_name,
                            "event_date"    => $webinar[0]->event_date,
                            "event_start"   => $webinar[0]->event_start,
                            "event_end"     => $webinar[0]->event_end,
                            "event_picture" => env("WEBINAR_URL") . $webinar[0]->event_picture,
                            "registered"    => count($registered),
                            'quota'         => 500,
                            'student'       => $student,
                            'is_certificate' => $webinar[0]->is_certificate,
                            'certificate'   => $path_zip
                        );

                        return $response;
                    } else {
                        return (object) array(
                            'message' => 'Data not found'
                        );
                    }
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

    public function addNormalWebinar(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'event_name'    => 'required|string',
            'event_date'    => 'required|date_format:Y-m-d',
            'event_picture' => 'required|mimes:jpg,jpeg,png|max:2000',
            'event_link'    => 'required|url',
            'event_start'   => 'required|date_format:H:i:s',
            'event_end'     => 'required|date_format:H:i:s|after:event_start',
            'price'         => 'numeric',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $duplicatename = DB::table($this->tbWebinar)
                    ->where("event_name", "=", $request->event_name)
                    ->get();
                $samedaytime = DB::table($this->tbWebinar)
                    ->where("event_date", "=", $request->event_date)
                    ->where("event_start", "=", $request->event_start)
                    ->where("event_end", "=", $request->event_end)
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
                    if ($request->event_date > date('Y-m-d')) {
                        if ($file = $request->file('event_picture')) {
                            try {
                                $path = $file->store('webinar_internal', 'public');
                                $webinar = array(
                                    'event_name'    => $request->event_name,
                                    'event_link'    => $request->event_link,
                                    'event_date'    => $request->event_date,
                                    'event_picture' => $path,
                                    'event_start'   => $request->event_start,
                                    'event_end'     => $request->event_end,
                                    'price'         => $request->price,
                                    // 'is_deleted'=>true
                                );
                                $save =  DB::table($this->tbWebinar)->insertGetId($webinar);
                            } catch (Exception $e) {
                                echo $e;
                            }
                            $thisWebinar = DB::table($this->tbWebinar)
                                ->where('id', '=', $save)
                                ->select('*')
                                ->get();
                            $currency = "Rp " . number_format($request->price, 2, ',', '.');
                            $path_zip = null;

                            // if ($webinar[0]->is_certificate) {
                            //     $path_zip = env("WEBINAR_URL") . $webinar[0]->certificate;
                            // }
                            $response = array(
                                "id"            => $thisWebinar[0]->id,
                                "event_name"    => $request->event_name,
                                "event_date"    => $request->event_date,
                                "event_picture" => env("WEBINAR_URL") . $thisWebinar[0]->event_picture,
                                "event_link"    => $request->event_link,
                                'event_start'   => $request->event_start,
                                'event_end'     => $request->event_end,
                                'price'         => $currency,
                                "is_certificate"    => $thisWebinar[0]->is_certificate,
                                "certificate"       => $thisWebinar[0]->certificate,
                            );

                            return $response;
                        }
                    } else {
                        return (object) array(
                            'message' => 'the event must be after today!'
                        );
                    }
                }
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
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
    public function editWebinar(Request $request, $webinar_id)
    {
        //validasi 
        $array_validation = array(
            'webinar_id'    => $webinar_id,
            'event_name'    => $request->event_name,
            'event_date'    => $request->event_date,
            'event_link'    => $request->event_link,
            'event_start'   => $request->event_start,
            'event_end'     => $request->event_end,
            'event_picture' => $request->event_picture
        );
        $validation = Validator::make($array_validation, [
            'webinar_id'    => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'event_name'    => 'nullable|string',
            'event_date'    => 'nullable|date_format:Y-m-d',
            'event_link'    => 'nullable|url',
            'event_start'   => 'nullable|date_format:H:i:s',
            'event_end'     => 'nullable|date_format:H:i:s|after:event_start',
            // 'price' => 'numeric|required',
            'event_picture' => 'nullable|mimes:jpg,jpeg,png|max:2000'
        ]);
        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            //find webinar id
            // $webinar = DB::table($this->tbWebinar)
            //     ->where('id', '=', $webinar_id)
            //     ->select('id as webinar_id', 'event_picture as path', 'event_name', 'event_date', 'event_link', 'event_start', 'event_end', 'is_certificate', 'certificate')
            //     ->get();
            $webinar = CareerSupportModelsWebinarBiasa::findOrFail($webinar_id);
            //set modified
            if (!empty($webinar)) {
                $data = DB::transaction(function () use ($request, $webinar, $webinar_id) {
                    $path = $webinar->event_picture;
                    if ($file = $request->file('event_picture')) {
                        $path = $file->store('webinar_internal', 'public');
                    }
                    $datetime = Carbon::now();
                    $datetime->toDateTimeString();
                    $edited = array(
                        'event_name' => $this->checkparam($request->event_name, $webinar->event_name),
                        'event_date' => $this->checkparam($request->event_date, $webinar->event_date),
                        'event_link' => $this->checkparam($request->event_link, $webinar->event_link),
                        'event_start' => $this->checkparam($request->event_start, $webinar->event_start),
                        'event_end' => $this->checkparam($request->event_end, $webinar->event_end),
                        'price' => $this->checkparam($request->price, $webinar->price),
                        'modified' => $datetime,
                        'event_picture' => $path
                    );
                    DB::table($this->tbWebinar)
                        ->where('id', '=', $webinar_id)
                        ->update($edited);

                    $tableUpdated = DB::table($this->tbWebinar)
                        ->where('id', '=', $webinar_id)
                        ->select('*')
                        ->get();
                    $currency = "Rp " . number_format($request->price, 2, ',', '.');
                    $path_zip = null;

                    if ($webinar->is_certificate) {
                        $path_zip = env("WEBINAR_URL") . $webinar->certificate;
                    }
                    $response = array(
                        "id"            => $request->webinar_id,
                        "event_name"    => $request->event_name,
                        "event_date"    => $request->event_date,
                        "event_picture" => env("WEBINAR_URL") . $tableUpdated[0]->event_picture,
                        "zoom_link"     => $request->event_link,
                        'event_start'   => $request->event_start,
                        'event_end'     => $request->event_end,
                        'price'         => $currency,
                        "is_certificate"    => $webinar->is_certificate,
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
                    $message = "failed";
                    $code = 400;
                }
            }
            return $this->makeJSONResponse($message, $code);
        }
    }
    public function destroyWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $webinar_id)
                ->get();
            $delete = CareerSupportModelsWebinarBiasa::findOrfail($webinar_id);
            $name = str_replace(' ', '_', $webinar[0]->event_name);
            $path = 'certificate_internal/webinar_' . $name;
            if ($delete) {
                if (Storage::disk('public')->exists($delete->event_picture)) {
                    Storage::disk('public')->delete($delete->event_picture);
                    Storage::disk('public')->deleteDirectory($path);
                    $delete->delete();

                    $message = "sucessfully delete webinar!";
                    $code = 200;

                    return $this->makeJSONResponse(["message" => $message], $code);
                } else {
                    $message = "can't delete...";
                    $code = 500;

                    return $this->makeJSONResponse(["message" => $message], $code);
                }
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
        $validation = Validator::make($request->all(), [
            'page' => 'numeric'
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
                $total_page = 0;
                $total_count = 0;
                $checkWebinar = DB::table($this->tbWebinar)->get();

                if (count($checkWebinar) > 0) {
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

                        for ($i = 0; $i < count($webinar); $i++) {
                            $currency = "Rp " . number_format($webinar[$i]->price, 2, ',', '.');

                            $participant = DB::table($this->tbParticipant, 'participant')
                                ->leftJoin($this->tbOrder . ' as order', 'participant.id', '=', 'order.participant_id')
                                ->where('order.webinar_id', '=', $webinar[$i]->id)
                                ->where('order.status', '=', 'success')
                                ->select('participant.student_id')
                                ->get();

                            $listSchool = [];
                            $participantSchoolArray = [];
                            for ($j = 0; $j < count($participant); $j++) {
                                $participantSchool = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                    ->leftJoin($this->tbSchool . ' as school', 'student.school_id', '=', 'school.id')
                                    ->where('student.id', '=', $participant[$j]->student_id)
                                    ->select('student.school_id')
                                    ->get();
                                $participantSchoolArray[$j] = $participantSchool[0];
                                $school = DB::connection('pgsql2')->table($this->tbSchool)
                                    ->where('id', '=', $participantSchoolArray[$j]->school_id)
                                    ->get();

                                $listSchool[$j] = $school[0];
                            }
                            $unique = array_values(array_unique($listSchool, SORT_REGULAR));
                            $path_zip = null;

                            if ($webinar[0]->is_certificate) {
                                $path_zip = env("WEBINAR_URL") . $webinar[0]->certificate;
                            }
                            $data[$i] = (object) array(
                                'id'                => $webinar[$i]->id,
                                'event_name'        => $webinar[$i]->event_name,
                                'event_date'        => $webinar[$i]->event_date,
                                'event_start'       => $webinar[$i]->event_start,
                                'event_end'         => $webinar[$i]->event_end,
                                'event_picture'     => env("WEBINAR_URL") . $webinar[$i]->event_picture,
                                'schools'           => $unique,
                                'event_link'        => $webinar[$i]->event_link,
                                'price'             => $currency,
                                "is_certificate"    => $webinar[$i]->is_certificate,
                                "certificate"       => $path_zip,
                            );
                        }
                    }
                    $total_count = $webinar_count[0]->count;

                    $response = (object) array(
                        'data' => $data,
                        'pagination' => (object) array(
                            'first_page' => 1,
                            'last_page' => $total_page,
                            'current_page' => $current_page,
                            'current_data' => count($webinar),
                            'total_data' => $total_count
                        )
                    );

                    return $response;
                } else {

                    $response = (object) array(
                        'data' => $data,
                        'pagination' => (object) array(
                            'first_page' => 1,
                            'last_page' => $total_page,
                            'current_page' => $current_page,
                            'current_data' => count($webinar),
                            'total_data' => $total_count
                        )
                    );

                    return $response;
                }
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
    public function listWebinarStudent(Request $request)
    {
        /*
        Param:
        1. Page -> default(0 or null)
        2. Search -> default(null) -> search by webinar event name
        */
        $validation = Validator::make($request->all(), [
            'page' => 'numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $current_page = 1;

                $query_pagination = "";
                $query_search = "";
                $start_item = 0;
                $webinar = [];
                $data = array();
                $list = array();
                $total_page = 0;
                $total_count = 0;
                $checkWebinar = DB::table($this->tbWebinar)->get();

                if (count($checkWebinar) > 0) {
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

                        for ($i = 0; $i < count($webinar); $i++) {
                            $currency = "Rp " . number_format($webinar[$i]->price, 2, ',', '.');

                            $participant = DB::table($this->tbParticipant, 'participant')
                                ->leftJoin($this->tbOrder . ' as order', 'participant.id', '=', 'order.participant_id')
                                ->where('order.webinar_id', '=', $webinar[$i]->id)
                                ->where('order.status', '=', 'success')
                                ->select('participant.student_id')
                                ->get();

                            $listSchool = [];
                            $participantSchoolArray = [];
                            for ($j = 0; $j < count($participant); $j++) {
                                $participantSchool = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                    ->leftJoin($this->tbSchool . ' as school', 'student.school_id', '=', 'school.id')
                                    ->where('student.id', '=', $participant[$j]->student_id)
                                    ->select('student.school_id')
                                    ->get();
                                $participantSchoolArray[$j] = $participantSchool[0];
                                $school = DB::connection('pgsql2')->table($this->tbSchool)
                                    ->where('id', '=', $participantSchoolArray[$j]->school_id)
                                    ->get();

                                $listSchool[$j] = $school[0];
                            }
                            $unique = array_values(array_unique($listSchool, SORT_REGULAR));
                            $path_zip = null;

                            if ($webinar[0]->is_certificate) {
                                $path_zip = env("WEBINAR_URL") . $webinar[0]->certificate;
                            }
                            $data[$i] = array(
                                'id'                => $webinar[$i]->id,
                                'event_name'        => $webinar[$i]->event_name,
                                'event_date'        => $webinar[$i]->event_date,
                                'event_picture'     => env("WEBINAR_URL") . $webinar[$i]->event_picture,
                                'event_link'        => $webinar[$i]->event_link,
                                'price'             => $webinar[$i]->price

                            );
                        }
                        $list = $data;
                    }
                    return $list;
                } else {
                    return $list;
                }
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
}
