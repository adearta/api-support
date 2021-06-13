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
    public function getDetailOrder(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $detail = DB::table($this->tbWebinar, 'webinar')
                ->leftJoin($this->tbParticipant . ' as participant', 'participant.webinar_id', '=', 'webinar.id')
                ->leftJoin($this->tbOrder . ' as pesan', 'participant.id', '=', 'pesan.participant_id')
                ->where('webinar.id', '=', $request->webinar_id)
                ->where('participant.student_id', '=', $request->student_id)
                ->select('webinar.event_name', 'webinar.event_date', 'webinar.event_picture', 'webinar.event_link', 'webinar.event_start', 'webinar.event_end', 'webinar.price', 'pesan.order_id', 'pesan.status')
                ->get();

            if (count($detail) > 0) {
                return $this->makeJSONResponse($detail, 200);
            } else {
                return $this->makeJSONResponse(['message' => 'Data not found'], 202);
            }
        }
    }
}
