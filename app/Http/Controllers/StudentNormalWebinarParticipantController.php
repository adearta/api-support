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
        $this->tbOrder = CareerSupportModelsOrders::tableName();
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
            $profilePercentage = DB::connection('pgsql2')->select('select percent.percent from ' . $this->tbPercentage . " as percent left join " . $this->tbStudent . " as std on percent.user_id = std.creator_id where percent.user_id = " . $request->student_id);
            if ($profilePercentage[0]->percent < 60) {
                return $this->makeJSONResponse(["message" => "please complete your profile, minimum 60% profile required"], 200);
                // return response($profilePercentage, 200);
            } else {
                //register
                // foreach ($request->webinar_id as $w) {
                $webinar = DB::table($this->tbWebinar)
                    ->where('id', '=', $request->webinar_id)
                    ->get();
                //simpan ke participants
                DB::table($this->tbParticipant)->insert(array(
                    'webinar_id' => $request->webinar_id,
                    'student_id' => $request->student_id,
                ));
                //simpan ke order
                DB::table($this->tbOrder)->insert(array(
                    'student_id' => $request->student_id,
                    'webinar_id' => $request->webinar_id,
                    // 'transaction_id'=>$request->transaction_id, oleh method updatee
                    // 'token'=>$request->order_id, update oleh case 4
                    // 'status'=>$request->status, update oleh method update
                ));
                //simpan ke notif
                DB::table($this->tbNotif)->insert(array(
                    'student_id' => $request->student_id,
                    'webinar_normal_id' => $request->webinar_id,
                    'message_id'    => "Anda telah mendaftar untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_time,
                    'message_en'    => "You have been register to join a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_time
                ));
                // }
                $message = "sucessfully register to webinar!";
                $code = 200;
                // echo $profilePercentage;
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
                    // case 1:
                    //     //dapet reminder
                    //     //cron job buat ngirim email pemberitahuan ke yang belum bayar,
                    //     //caranya select nanti student_id di tbl order cari nanti yang statusnya masih 1 trus select email di tabel student 
                    //     //cari yang id nya sama trus bikin job buat ngirim ke email itu.
                    //     $count = DB::select("select count(id) from " . $this->tbOrder . " where status = 1");
                    //     for ($i = 0; $i < $count; $i++) {
                    //         $status = DB::table($this->tbOrder)
                    //             ->where('status', '=', '1')
                    //             ->select('student_id')
                    //             ->get();

                    //         $student = DB::connection('pgsql2')->table($this->tbStudent)
                    //             ->where('id', '=', $status)
                    //             ->select('email')
                    //             ->get();

                    //         //send email using student
                    //     }
                    //     $message = "sucessfully send payment mail to student";
                    //     $code = 200;
                    //     return $this->makeJSONResponse($message, $code);
                    //     break;
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
