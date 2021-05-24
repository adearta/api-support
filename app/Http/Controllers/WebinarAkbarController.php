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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendMailReminderJob;
use App\Mail\SendMailReminder;
use App\Mail\SendSchoolMailInvitation;

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
    public function detailWebinar($webinar_id)
    {
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
                        $webinar = array(
                            'zoom_link' => $request->zoom_link,
                            'event_name' => $request->event_name,
                            'event_date' => $request->event_date,
                            'event_time' => $request->event_time,
                            'event_picture' => $path
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
                            // Mail::to($school[0]->email)->send(new SendSchoolMailInvitation($webinar, $school));
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

        // // send all mail in the queue.
        // $job = (new \App\Jobs\SendBulkQueueEmail($details))
        //     ->delay(
        //         now()
        //             ->addSeconds(2)
        //     );

        // $this->dispatch($job);

        return $this->makeJSONResponse(['message' => 'email sent!'], 200);
    }

    public function participantList($webinar_id)
    {
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
