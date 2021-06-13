<?php

namespace App\Http\Controllers;

use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\CareerSupportModelsWebinarBiasa;
use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CareerSupportModelsPercentage;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\NotificationWebinarModel;
use App\Models\StudentModel;

class StudentNormalWebinarParticipantController extends Controller
{
    use ResponseHelper;

    //
    private $tbWebinar;
    private $tbParticipant;
    private $tbNotif;
    private $tbPercentage;
    private $tbOrder;
    private $tbStudent;
    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbPercentage = CareerSupportModelsPercentage::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbStudent = StudentModel::tableName();
    }

    public function registerStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $message = " ";
                $code = " ";
                $profilePercentage = DB::connection('pgsql2')->select('select percent.percent, std.id as student_id from ' . $this->tbPercentage . " as percent left join " . $this->tbStudent . " as std on percent.user_id = std.creator_id where percent.user_id = " . $request->student_id);
                $registered = DB::select("select count(pesan.webinar_id) as registered from " . $this->tbOrder . " as pesan left join " . $this->tbWebinar . " as web on web.id = pesan.webinar_id where pesan.status != 'order' and pesan.status != 'expire'");
                // check if the student have the percent of profile or percent of profile is under 60
                if (empty($profilePercentage) || $profilePercentage[0]->percent < 60) {
                    $message = "please complete your profile, minimum 60% profile required";
                    $code = 202;
                } else {
                    if ($registered[0]->registered < 500) {
                        //register
                        $result = DB::transaction(function () use ($request, $profilePercentage, $message, $code) {
                            $webinar = DB::table($this->tbWebinar)
                                ->where('id', '=', $request->webinar_id)
                                ->get();

                            //get the student data from list of participant webinar
                            $pariticipant = DB::table($this->tbParticipant, 'participant')
                                ->leftJoin($this->tbWebinar . " as web", 'web.id', '=', 'participant.webinar_id')
                                ->where('participant.student_id', '=', $profilePercentage[0]->student_id)
                                ->where('web.event_date', '=', $webinar[0]->event_date)
                                ->where('web.event_start', '<=', $webinar[0]->event_start)
                                ->where('web.event_end', '>=', $webinar[0]->event_start)
                                ->where('web.event_start', '<=', $webinar[0]->event_end)
                                ->where('web.event_end', '>=', $webinar[0]->event_end)
                                ->get();

                            //check if the student has been registered on other webinar with the same time before            
                            if (count($pariticipant) > 0) {
                                $message = "Cannot register to this event because this student has been registered on other webinar with the same time before";
                                $code = 202;
                            } else {
                                //inser to participant table
                                $participant = DB::table($this->tbParticipant)->insertGetId(array(
                                    'webinar_id' => $request->webinar_id,
                                    'student_id' => $profilePercentage[0]->student_id,
                                ));

                                //simpan ke order
                                DB::table($this->tbOrder)->insert(array(
                                    'participant_id' => $participant,
                                    'webinar_id' => $request->webinar_id,
                                    //
                                ));
                                //simpan ke notif
                                DB::table($this->tbNotif)->insert(array(
                                    'student_id' => $profilePercentage[0]->student_id,
                                    'webinar_normal_id' => $request->webinar_id,
                                    'message_id'    => "Anda telah mendaftar untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_start,
                                    'message_en'    => "You have been register to join a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_start
                                ));

                                $message = "Success to register student to this webinar";
                                $code = 200;
                            }

                            return array(
                                'status'    => true,
                                'message'   => $message,
                                'code'      => $code
                            );
                        });
                        if ($result) {
                            $message = $result['message'];
                            $code = $result['code'];
                        } else {
                            $message = "failed";
                            $code = 400;
                        }
                    } else {
                        $message = $registered;
                        $code = 400;
                    }
                }
                return $this->makeJSONResponse(["message" => $message], $code);
            });
            if ($data) {
                return $this->makeJSONResponse($data->original, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
}
