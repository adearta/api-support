<?php

namespace App\Http\Controllers;

use App\Models\NotificationWebinarModel;
use App\Models\SchoolParticipantAkbarModel;
use Illuminate\Http\Request;
use App\Models\WebinarAkbarModel;
use App\Models\StudentParticipantAkbarModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WebinarAkbarController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbNotification;
    private $tbSchoolParticipants;
    private $tbStudentParticipants;

    public function __construct()
    {
        $this->tbWebinar = WebinarAkbarModel::tableName();
        $this->tbSchoolParticipants = SchoolParticipantAkbarModel::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
        $this->tbStudentParticipants = StudentParticipantAkbarModel::tableName();
    }

    public function getWebinarBySchoolId($id)
    {
        //id -> school_id
        try {
            $selectCount = "select count('id') from " . $this->tbStudentParticipants . " as student where student.webinar_id = web.id";
            $webinar = DB::select("select sch.status, web.zoom_link, web.event_name, web.event_date, web.event_time, web.event_picture, (500) as quota, (" . $selectCount . ") as registered from " . $this->tbSchoolParticipants . " as sch right join " . $this->tbWebinar . " as web on sch.webinar_id = web.id where sch.school_id = " . $id . " order by web.id desc");

            return $this->makeJSONResponse($webinar, 200);
        } catch (Exception $e) {
            echo $e;
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
            if ($request->event_date > date("Y-m-d")) {
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
                                'webinar_akbar_id' => $webinarId,
                                'message_id'    => "Anda mendapatkan undangan untuk mengikuti Webinar dengan judul " . $request->event_name . " pada tanggal " . $request->event_date . " dan pada jam " . $request->event_time,
                                'message_en'    => "You get an invitation to join in a webinar with a title" . $request->event_name . " on " . $request->event_date . " and at " . $request->event_time
                            ));
                        }

                        //$this->sendMail($request); -> ada error kk, monggo di cek sendiri ya
                    } catch (Exception $e) {
                        echo $e;
                        //return $this->makeJSONResponse(["message" => "failed insert the data to database"], 200);
                    }

                    return $this->makeJSONResponse(["message" => "Success to save data to database"], 200);
                }
            } else {
                return $this->makeJSONResponse(["message" => "The event date must be after today "], 202);
            }
        }
    }

    public function addSchoolParticipants(Request $request)
    {
        try {
            $webinar = DB::table($this->tbWebinar)->where('id', '=', $request->webinar_id)->get();
            foreach ($request->school_id as $s) {
                $school = DB::table($this->tbSchoolParticipants)
                    ->where('school_id', '=', $s)
                    ->where('webinar_id', '=', $webinar[0]->id)
                    ->get();

                if (count($school) == 0) {
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

                    return $this->makeJSONResponse(['message' => "Success to add schools to this event"], 200);
                } else {
                    $response = array(
                        "message" => "This school has been added to this event",
                        "school_id" => $school[0]->school_id
                    );

                    return $this->makeJSONResponse($response, 202);
                }
            }
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function destroy($id)
    {
        $delete = WebinarAkbarModel::findOrfail($id);
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

        // $listEmail = ['adearta48@gmail.com', 'adearta@student.ub.ac.id', 'hebryclover@gmail.com'];

        // // School::create($request->all());
        // Mail::send('email', array(
        //     'zoom_link' => $request->get('zoom_link'),
        //     'name' => $request->get('name'),
        //     'date' => $request->get('date'),
        //     'time' => $request->get('time'),
        //     // 'email' => $request->get('email'),
        // ), function ($message) use ($request, $listEmail) {
        //     //selecting all email from career_support_models_student_participants and save to array
        //     //
        //     // $broadcast = DB::select('select school_email from career_support_models_school');
        //     // $listEmail = array();
        //     // while ($row = pg_fetch_assoc($broadcast)) {

        //     //     // add each row returned into an array
        //     //     $listEmail[] = $row;

        //     //     // OR just echo the data:
        //     //     // echo $row['username']; // etc
        //     // }
        //     //
        //     // $broadcast = "suastikaadinata97@gmail.com";
        //     $message->from('adeartakusumaps@gmail.com');
        //     //diganti broadcast ke semua email student participants.
        //     $message->to($listEmail)->subject($request->get('subject'));
        // });
        $details = [
            'subject' => 'Weekly Notification'
        ];

        // send all mail in the queue.
        $job = (new \App\Jobs\SendBulkQueueEmail($details))
            ->delay(
                now()
                    ->addSeconds(2)
            );

        $this->dispatch($job);

        return $this->makeJSONResponse(['message' => 'email sent!'], 200);
    }
}
