<?php

namespace App\Http\Controllers;

use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\CareerSupportModelsWebinarBiasa;
use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Exception;
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
            'webinar_id' => 'required|numeric',
            'student_id' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $message = " ";
            $code = " ";
            $profilePercentage = DB::connection('pgsql2')->select('select percent.percent, std.id as student_id from ' . $this->tbPercentage . " as percent left join " . $this->tbStudent . " as std on percent.user_id = std.id where percent.user_id = " . $request->student_id);

            // check if the student have the percent of profile or percent of profile is under 60
            if (empty($profilePercentage) || $profilePercentage[0]->percent < 60) {
                $message = "please complete your profile, minimum 60% profile required";
                $code = 202;
            } else {
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
                        ->where($webinar[0]->start_time, '>=', 'web.start_time')
                        ->where($webinar[0]->start_time, '<=', 'web.end_time')
                        ->where($webinar[0]->end_time, '>=', 'web.start_time')
                        ->where($webinar[0]->end_time, '<=', 'web.end_time')
                        ->get();

                    //check if the student has been registered on other webinar with the same time before            
                    if (count($pariticipant) > 0) {
                        $message = "Cannot register to this event because this student has been registered on other webinar with the same time before";
                        $code = 202;
                    } else {
                        //inser to participant table
                        DB::table($this->tbParticipant)->insert(array(
                            'webinar_id' => $request->webinar_id,
                            'student_id' => $profilePercentage[0]->student_id,
                        ));

                        //simpan ke order
                        DB::table($this->tbOrder)->insert(array(
                            'student_id' => $profilePercentage[0]->student_id,
                            'webinar_id' => $request->webinar_id,

                        ));
                        //simpan ke notif
                        DB::table($this->tbNotif)->insert(array(
                            'student_id' => $request->student_id,
                            'webinar_normal_id' => $request->webinar_id,
                            'message_id'    => "Anda telah mendaftar untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->start_time,
                            'message_en'    => "You have been register to join a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->start_time
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
                    return $this->makeJSONResponse(['message' => $result['message']], $result['code']);
                } else {
                    return $this->makeJSONResponse(['message' => 'failed'], 400);
                }
            }
            return $this->makeJSONResponse(["message" => $message], $code);
        }
    }
    //only test query
    public function status()
    {
        // $datesquery = "web.event_date = current_date + interval '" . $day . "' day";
        $event = DB::select("select * from " . $this->tbParticipant);
        $data = DB::table($this->tbOrder)
            ->where("student_id", "=", $event[0]->student_id)
            ->select("status")
            ->get();

        return $this->makeJSONResponse($data, 200);
    }
    public function updateStatusStudentParticipant(Request $request)
    {

        try {
            // $webinar = DB::table($this->tbWebinar)
            //     ->where('id', '=', $request->webinar_id)
            //     ->get();
            switch ($request->status) {
                case 3:
                    $validation = Validator::make($request->all(), [
                        'webinar_id' => 'required|numeric',
                        'student_id' => 'required|numeric',
                        'status' => 'required|numeric',
                        // 'transaction_id'=>'',
                        // 'order_id'=>'',
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        DB::table($this->tbOrder)
                            ->where('webinar_id', '=', $request->webinar_id)
                            ->where('student_id', '=', $request->studen_id)
                            ->update(['status' => $request->status]);
                    }
                    $message = "webinar status booked, We kindly request you settle the payment request immediatelly before (time)";
                    $code = 200;
                    break;

                case 4:
                    //pilih dari tabel order sesuai dengan id yang dimasukkan 
                    //ubah statusnya mejadi 4 
                    //nantinya ini yang akan dgunakan sebagai paticipants
                    //simpan token ke kolom token di tabel order

            }
        } catch (Exception $e) {
            echo $e;
        }
    }
}
