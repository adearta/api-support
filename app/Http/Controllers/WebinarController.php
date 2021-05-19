<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebinarAkbarModel;
use App\Models\NotificationCandidateModel;
use App\Models\SchoolParticipantsCandidateModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebinarController extends Controller
{
    //
    use ResponseHelper;
    private $tbWebinar;
    private $tbNotification;
    private $tbSchoolParticipants;

    public function __construct()
    {
        $this->tbWebinar = WebinarAkbarModel::tableName();
        $this->tbSchoolParticipants = SchoolParticipantsCandidateModel::tableName();
        $this->tbNotification = NotificationCandidateModel::tableName();
    }

    public function getWebinar()
    {
        $webinar =  DB::select('select * from career_support_models_webinar_akbar');
        $auth = auth()->user();
        if ($auth) {
            return $this->makeJSONResponse($webinar, 200);
        } else {
            $message = "no data!";
            return $this->makeJSONResponse($message, 400);
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
            //create data
            if ($file = $request->file('event_picture')) {
                try {
                    $path = $file->store('webinar', 'uploads');
                    $webinarId = DB::table($this->tbWebinar)->insertGetId(array(
                        'zoom_link' => $request->zoom_link,
                        'event_name' => $request->event_name,
                        'event_date' => $request->event_date,
                        'event_time' => $request->event_time,
                        'event_picture' => $path
                    ));

                    foreach ($request->school_id as $s) {
                        DB::table($this->tbSchoolParticipants)->insert(array(
                            'webinar_id'    => $webinarId,
                            'school_id'     => $s,
                        ));

                        DB::table($this->tbNotification)->insert(array(
                            'school_id'     => $s,
                            'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $request->name,
                            'message_en'    => "You get an invitation to join in a webinar with a title" . $request->name
                        ));
                    }

                    //$this->sendMail($request); -> ada error kk, monggo di cek sendiri ya
                } catch (Exception $e) {
                    echo $e;
                    //return $this->makeJSONResponse(["message" => "failed insert the data to database"], 200);
                }

                return $this->makeJSONResponse(["message" => "Success to save data to database"], 200);
            }
        }
    }
    public function updateWebinar(Request $request, $id)
    {
    }
    public function destroy($id)
    {
        $delete = WebinarCandidateModel::findOrfail($id);
        $auth = auth()->user();
        if ($auth) {
            $delete->delete();
            $message_ok = "deleted!";
            return $this->makeJSONResponse($message_ok, 200);
        } else {
            $message_err = "cant find data!";
            return $this->makeJSONResponse($message_err, 400);
        }
    }


    public function sendMail(Request $request)
    {
        // School::create($request->all());
        Mail::send('email', array(
            'zoom_link' => $request->get('zoom_link'),
            'name' => $request->get('name'),
            'date' => $request->get('date'),
            'time' => $request->get('time'),
            // 'email' => $request->get('email'),
        ), function ($message) use ($request) {
            //selecting all email from career_support_models_student_participants and save to array
            // $broadcast = DB::select('select email from career_support_models_student_participants');
            $broadcast = "suastikaadinata97@gmail.com";
            $message->from('adeartakusumaps@gmail.com');
            //diganti broadcast ke semua email student participants.
            $message->to($broadcast, 'Hello Student')->subject($request->get('subject'));
        });
    }
}
