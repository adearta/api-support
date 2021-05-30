<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\CareerSupportModelsOrders;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Validator;

class WebinarOrderController extends Controller
{
    use ResponseHelper;
    private $tbWebinar;
    private $tbOrder;

    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbOrder = CareerSupportModelsOrders::tableName();
    }

    //get the detail of webinar + order status by student
    public function getDetailOrder(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'webinar_id' => 'required|numeric',
            'student_id' => 'required|numeric'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $detail = DB::table($this->tbWebinar, 'webinar')
                ->leftJoin($this->tbOrder . ' as pesan', 'webinar.id', '=', 'pesan.webinar_id')
                ->where('webinar.id', '=', $request->webinar_id)
                ->where('pesan.student_id', '=', $request->student_id)
                ->select('webinar.event_name', 'webinar.event_date', 'webinar.event_picture', 'webinar.event_link', 'webinar.start_time', 'webinar.end_time', 'webinar.price', 'pesan.order_id', 'pesan.status')
                ->get();

            if (count($detail) > 0) {
                return $this->makeJSONResponse($detail, 200);
            } else {
                return $this->makeJSONResponse(['message' => 'Data not found'], 202);
            }
        }
    }
}
