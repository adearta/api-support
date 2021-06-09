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


    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbSchool = SchoolModel::tableName();
    }



    public function listNormalWebinar()
    {
        //count data fom tabel participant to get the registerd
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
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $webinar = DB::table($this->tbWebinar)
                    ->where('id', '=', $webinar_id)
                    ->get();
                $participant = DB::table($this->tbParticipant, 'participant')
                    ->leftJoin($this->tbOrder . ' as order', 'participant.id', '=', 'order.participant_id')
                    ->where('order.webinar_id', '=', $webinar_id)
                    ->where('order.status', '=', 'success')
                    ->select('participant.student_id')
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

                if (count($webinar) > 0) {
                    $responsea = array(
                        "id"   => $webinar_id,
                        "event_name" => $webinar[0]->event_name,
                        "event_date" => $webinar[0]->event_date,
                        "event_start"    => $webinar[0]->event_start,
                        "event_end"      => $webinar[0]->event_end,
                        "event_picture" => $webinar[0]->event_picture,
                        "schools"    => $unique,
                        "event_link" => $webinar[0]->event_link,
                        "is_certificate" => false,
                        "certificate" => "link not found",
                    );

                    return $this->makeJSONResponse($responsea, 200);
                } else {
                    return $this->makeJSONResponse(['message' => 'Data not found'], 202);
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
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $webinar = DB::table($this->tbWebinar)
                    ->where('id', '=', $webinar_id)
                    ->get();

                $registered = DB::table($this->tbWebinar, 'webinar')
                    ->leftJoin($this->tbParticipant . ' as participant', 'webinar.id', '=', 'participant.webinar_id')
                    ->leftJoin($this->tbOrder . ' as pesan', 'participant.id', '=', 'pesan.participant_id')
                    ->where('webinar.id', '=', $webinar_id)
                    ->where('pesan.status', '!=', 'order')
                    ->where('pesan.status', '!=', 'expire')
                    ->select('participant.student_id', 'pesan.status')
                    ->get();

                if (count($webinar) > 0) {
                    $student = array();

                    if (count($registered) > 0) {
                        for ($i = 0; $i < count($registered); $i++) {
                            $temp = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                ->leftJoin($this->tbSchool . ' as school', 'student.school_id', '=', 'school.id')
                                ->where('student.id', '=', $registered[$i]->student_id)
                                ->select('student.name as student_name', 'school.name as school_name')
                                ->get();

                            $student[$i] = array(
                                "student_id"  => $registered[$i]->student_id,
                                "student_name" => $temp[0]->student_name,
                                "school_name" => $temp[0]->school_name,
                                "participant_status" => $registered[$i]->status
                            );
                        }
                    }

                    $response = array(
                        "event_id"      => $webinar_id,
                        "event_name"    => $webinar[0]->event_name,
                        "event_date"    => $webinar[0]->event_date,
                        "event_start"    => $webinar[0]->event_start,
                        "event_end"      => $webinar[0]->event_end,
                        "event_picture" => $webinar[0]->event_picture,
                        "registered"    => count($registered),
                        'quota'         => 500,
                        'student'       => $student
                    );

                    return $this->makeJSONResponse($response, 200);
                } else {
                    return $this->makeJSONResponse(['message' => 'Data not found'], 202);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    public function addNormalWebinar(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'event_name' => 'required',
            'event_date' => 'required',
            'event_picture' => 'required|mimes:jpg,jpeg,png|max:2000',
            'event_link' => 'required|url',
            'event_start' => 'required',
            'event_end' => 'required',
            'price' => 'numeric|required',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 202);
        } else {
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
                                'event_name' => $request->event_name,
                                'event_link' => $request->event_link,
                                'event_date' => $request->event_date,
                                'event_picture' => $path,
                                'event_start' => $request->event_start,
                                'event_end' => $request->event_end,
                                'price' => $request->price,
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
                        $response = array(
                            "id"   => $thisWebinar[0]->id,
                            "event_name" => $request->event_name,
                            "event_date" => $request->event_date,
                            "event_picture" => $thisWebinar[0]->event_picture,
                            "event_link" => $request->event_link,
                            'event_start' => $request->event_start,
                            'event_end' => $request->event_end,
                            'price' => $currency,
                        );
                        return $this->makeJSONResponse($response, 200);
                    }
                } else {
                    return $this->makeJSONResponse(['message' => 'the event must be after today!'], 202);
                }
            }
        }
    }
    public function editWebinar(Request $request, $webinar_id)
    {
        //validasi 
        $validation = Validator::make($request->all(), [
            'event_name' => 'required',
            'event_date' => 'required',
            'event_link' => 'required|url',
            'event_start' => 'required',
            'event_end' => 'required',
            'price' => 'numeric|required',
            'event_picture' => 'mimes:jpg,jpeg,png|max:2000'
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 202);
        } else {
            //find webinar id
            $webinar = DB::table($this->tbWebinar)
                ->where('id', '=', $webinar_id)
                ->select('id as webinar_id', 'event_picture as path')
                ->get();
            //set modified
            if (!empty($webinar)) {
                $data = DB::transaction(function () use ($request, $webinar, $webinar_id) {
                    $path = $webinar[0]->path;
                    if ($file = $request->file('event_picture')) {
                        $path = $file->store('webinar_internal', 'public');
                    }
                    $datetime = Carbon::now();
                    $datetime->toDateTimeString();
                    $edited = array(
                        'event_name' => $request->event_name,
                        'event_date' => $request->event_date,
                        'event_link' => $request->event_link,
                        'event_start' => $request->event_start,
                        'event_end' => $request->event_end,
                        'price' => $request->price,
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
                    $response = array(
                        "id"   => $webinar_id,
                        "event_name" => $request->event_name,
                        "event_date" => $request->event_date,
                        "event_picture" => $tableUpdated[0]->event_picture,
                        "zoom_link" => $request->zoom_link,
                        'event_start' => $request->event_start,
                        'event_end' => $request->event_end,
                        'price' => $currency,
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
        $delete = CareerSupportModelsWebinarBiasa::findOrfail($webinar_id);
        if (!empty($delete)) {
            if (Storage::disk('public')->exists($delete->event_picture)) {
                Storage::disk('public')->delete($delete->event_picture);
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

        $webinar_count = DB::select('select count(id) from ' . $this->tbWebinar);
        $total_page = ceil($webinar_count[0]->count / 10);

        if ($request->page == null || $request->page <= 1) {
            $current_page = 1;
        } else {
            $start_item = 0;
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

            $data[$i] = (object) array(
                'id'                => $webinar[$i]->id,
                'event_name'        => $webinar[$i]->event_name,
                'event_date'        => $webinar[$i]->event_date,
                'event_start'       => $webinar[$i]->event_start,
                'event_end'         => $webinar[$i]->event_end,
                'event_picture'     => $webinar[$i]->event_picture,
                'schools'           => $unique,
                'event_link'        => $webinar[$i]->event_link,
                'price'             => $currency,
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
                'current_data' => count($webinar),
                'total_data' => $webinar_count[0]->count
            )
        );

        return $this->makeJSONResponse($response, 200);
    }
}
