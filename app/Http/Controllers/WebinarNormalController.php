<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\CareerSupportModelsWebinarBiasa;
// use App\Models\CareerSupportModelsNotificationWebinarnormalModel;
use App\Models\StudentModel;
use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\NotificationWebinarModel;
use Illuminate\Support\Facades\DB;

class WebinarNormalController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbParticipant;
    private $tbNotif;
    private $tbStudent;
    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
    }

    public function listNormalWebinar()
    {
        //count data fom tabel participant to get the registerd
        try {
            $count = "select count('student.id') from " . $this->tbParticipant . " as student where student.webinar_id = web.id";
            $daysLeft = "select count('web.event_date - current_date') from " . $this->tbWebinar;
            //select data from table webinar where event date not this date.
            $data = DB::select("select web.id as webinar_id, web.event_link, web.event_name, web.event_date, web.event_time, web.start_time, web.end_time, web.price web.event_picture, (500) as quota, (" . $count . ") as registered,(" . $daysLeft . ") as Days_Left from " . $this->tbWebinar . " as web where web.event_date > current_date");

            return $this->makeJSONResponse($data, 200);
        } catch (Exception $e) {
            echo $e;
        }
    }
    public function detailNormalWebinar($webinar_id)
    {
        $validation = Validator::make(['webinar_id' => $webinar_id], [
            'webinar_id' => 'required|numeric'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbParticipant . " as student on student.webinar_id = web.id where web.id = ?", [$webinar_id]);

                $student = array();

                for ($i = 0; $i < count($data); $i++) {
                    $temp = DB::connection('pgsql2')->table($this->tbStudent)
                        ->where('id', '=', $data[$i]->student_id)
                        ->select('name', 'nim')
                        ->get();

                    $student[$i] = array(
                        // "id"=> $data[$i]->student_id,
                        "name" => $temp[0]->name,
                        "nim" => $temp[0]->nim,
                    );
                    $response = array(
                        "event_id"   => $webinar_id,
                        "event_name" => $data[0]->event_name,
                        "event_date" => $data[0]->event_date,
                        "event_time" => $data[0]->event_time,
                        "start_time" => $data[0]->start_time,
                        "end_time" => $data[0]->end_time,
                        "event_picture" => $data[0]->event_picture,
                        "student"    => $student
                    );
                    return $this->makeJSONResponse($response, 200);
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
            'start_time' => 'required',
            'end_time' => 'required',
            'price' => 'numeric|required',
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 202);
        } else {
            if ($request->event_date > date('Y-m-d')) {
                if ($file = $request->file('event_picture')) {
                    try {
                        $path = $file->store('webinarNormal', 'uploads');
                        $webinar = array(
                            'event_name' => $request->event_name,
                            'event_link' => $request->event_link,
                            'event_date' => $request->event_date,
                            'event_picture' => $path,
                            'start_time' => $request->start_time,
                            'end_time' => $request->end_time,
                            'price' => $request->price
                        );
                        //masukan ke tabel webinar
                        DB::table($this->tbWebinar)->insert($webinar);
                    } catch (Exception $e) {
                        echo $e;
                    }
                    return $this->makeJSONResponse(['message' => 'successfully save data webinar to database'], 200);
                }
            } else {
                return $this->makeJSONResponse(['message' => 'the event must be after today!'], 202);
            }
        }
    }
    //
}
