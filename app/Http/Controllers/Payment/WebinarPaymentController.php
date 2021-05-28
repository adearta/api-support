<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\CareerSupportModelsOrders;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\StudentModel;
use CareerSupportModelsOrders as GlobalCareerSupportModelsOrders;
use Illuminate\Support\Facades\DB;
use Veritrans_Config;
use Veritrans_Snap;
use Veritrans_Transaction;
use App\Traits\ResponseHelper;

class WebinarPaymentController extends Controller
{
    use ResponseHelper;

    private $tbOrder;
    private $tbStudent;
    private $tbWebinar;

    public function __construct()
    {
        Veritrans_Config::$serverKey = 'SB-Mid-server-nz9Nayf1uAfI0C-6TRgt5AK9'; //sendbox serverkey
        Veritrans_Config::$isProduction = false;
        Veritrans_Config::$isSanitized = true;
        Veritrans_Config::$is3ds = true;

        $this->tbOrder = CareerSupportModelsOrders::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
    }

    public function charge(Request $request)
    {
        $validation = Validator::make(['order_id' => $request->order_id], [
            'order_id' => 'required|numeric'
        ]);

        if ($validation->fails()) {
            return response($validation->errors(), 400);
        } else {
            $snapToken = "";
            $status = DB::transaction(function () use ($request) {
                $orderWebinar = DB::table($this->tbOrder, 'order')
                    ->leftJoin($this->tbWebinar . 'as webinar', 'order.webinar_id', '=', 'webinar.id')
                    ->where('order.id', '=', $request->order_id)
                    ->get();

                $student = DB::connection('pgsql2')
                    ->table($this->tbStudent)
                    ->where('id', '=', $orderWebinar[0]->student_id)
                    ->get();

                $transaction_details = array(
                    'order_id' => "WB00" . $student[0]->id . $orderWebinar[0]->id,
                    'gross_amount' => $orderWebinar[0]->price,
                );

                $item_details = array(
                    'id' => $orderWebinar[0]->id,
                    'price' => $orderWebinar[0]->price,
                    'name' => $orderWebinar[0]->event_name
                );

                $customer_detail = array(
                    'first_name' => $student[0]->name,
                    'email' => $student[0]->email,
                    'phone' => $student[0]->phone
                );

                $params = array(
                    'transaction_details' => $transaction_details,
                    'item_details' => $item_details,
                    'customers_detail' => $customer_detail
                );

                $this->snapToken = Veritrans_Snap::getSnapToken($params);
                return true;
            });

            return $status ? $this->makeJSONResponse(['token' => $snapToken], 200) : $this->makeJSONResponse((['message' => 'failed'], 400);
        }
    }

    public function updateStatus()
    {
        $notif = new Veritrans_Transaction();

        $transaction = $notif->transaction_status;
        $fraud = $notif->fraud_status;
        $order_id = $notif->order_id;
        $status = "failure";

        if ($fraud == "accept") {
            switch ($transaction) {
                case "capture":
                    $status = "success";
                    break;
                case "pending":
                    $status = "pending";
                    break;
                case "expire":
                    $status = "expire";
                    break;
            }
        }

        CareerSupportModelsOrders::where('order_id', $order_id)
            ->update(['status' => $status]);
    }
}
