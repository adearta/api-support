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
use App\Models\CareerSupportModelsOrders;
use App\Models\NotificationWebinarModel;

class StudentNormalWebinarParticipantController extends Controller
{
    use ResponseHelper;

    //
    private $tbWebinar;
    private $tbParticipant;
    private $tbNotif;
    private $tbPercentage;
    private $tbOrder;
    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbPercentage = CareerSupportModelsPercentage::tableName();
        $this->tbOrder = CareerSupportModelsOrders::tableName();
    }

    public function registerStudent(Request $request)
    {
        $profilePercentage = DB::connection('pgsql2')->select('select percentage from ' . $this->tbPercentage);
        if ($profilePercentage < 60) {
            return $this->makeJSONResponse(["message" => "please complete your profile, minimum 60% profile required"], 200);
        } else {
            //register
            $validation = Validator::make($request->all(), [
                'webinar_id' => 'required|numeric',
                'student_id' => 'required|numeric',
            ]);
            if ($validation->fails()) {
                return $this->makeJSONResponse($validation->errors(), 400);
            } else {

                foreach ($request->webinar_id as $w) {
                    $webinar = DB::table($this->tbWebinar)
                        ->where('id', '=', $request->webinar_id)
                        ->get();
                    //simpan ke participants
                    DB::table($this->tbParticipant)->insert(array(
                        'webinar_id' => $w,
                        'student_id' => $request->student_id,
                    ));
                    //simpan ke order
                    DB::table($this->tbOrder)->insert(array(
                        'student_id' => $request->student_id,
                        'webinar_id' => $w,
                        // 'transaction_id'=>$request->transaction_id,
                        // 'order_id'=>$request->order_id,
                        // 'status'=>$request->status,
                    ));
                    //simpan ke notif
                    DB::table($this->tbNotif)->insert(array(
                        'student_id' => $request->student_id,
                        'webinar_normal_id' => $w,
                        'message_id'    => "Anda telah mendaftar untuk mengikuti Webinar dengan judul " . $webinar->event_name . " pada tanggal " . $webinar->event_date . " dan pada jam " . $webinar->event_time,
                        'message_en'    => "You have been register to join a webinar with a title" . $webinar->event_name . " on " . $webinar->event_date . " and at " . $webinar->event_time
                    ));
                }
                $message = "sucessfully register to webinar!";
                $code = 200;
                return $this->makeJSONResponse($message, $code);
            }
        }
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
