<?php

namespace App\Http\Controllers;

use App\Models\CareerSupportModelsNormalStudentParticipants;
use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\StudentModel;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Validator;

class WebinarOrderController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbOrder;
    private $tbParticipant;
    private $tbStudent;

    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbStudent = StudentModel::tableName();
    }
    //get the detail of webinar + order status by student
    public function getDetailOrder($webinar_id, Request $request)
    {
        $student_id = $request->student_id;
        $needValidate = array(
            'webinar_id'    => $webinar_id,
            'student_id'    => $student_id
        );

        $validation = Validator::make($needValidate, [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $detail = DB::table($this->tbWebinar, 'webinar')
                ->leftJoin($this->tbParticipant . ' as participant', 'participant.webinar_id', '=', 'webinar.id')
                ->leftJoin($this->tbOrder . ' as pesan', 'participant.id', '=', 'pesan.participant_id')
                ->where('webinar.id', '=', $webinar_id)
                ->where('participant.student_id', '=', $student_id)
                ->select('webinar.event_name', 'webinar.event_date', 'webinar.event_picture', 'webinar.event_link', 'webinar.event_start', 'webinar.event_end', 'webinar.price', 'pesan.order_id', 'pesan.status', 'webinar.is_certificate', 'webinar.certificate', 'pesan.id as order_id')
                ->get();

            $path_zip = null;

            if ($detail[0]->is_certificate == true) {
                $path_zip = env("WEBINAR_URL") . $detail[0]->certificate;
            }
            if (count($detail) > 0) {
                $data = (object) array(
                    'event_name'        => $detail[0]->event_name,
                    'event_date'        => $detail[0]->event_date,
                    'event_picture'     => env("WEBINAR_URL") . $detail[0]->event_picture,
                    'event_link'        => $detail[0]->event_link,
                    'event_start'       => $detail[0]->event_start,
                    'event_end'         => $detail[0]->event_end,
                    'price'             => $detail[0]->price,
                    'order_id'          => $detail[0]->order_id,
                    'status'            => $detail[0]->status,
                    'is_certificate'    => $detail[0]->is_certificate,
                    'certificate'       => $path_zip,

                );
                return $this->makeJSONResponse(['data' => $data], 200);
            } else {
                return $this->makeJSONResponse(['message' => 'Data not found'], 400);
            }
        }
    }
}
