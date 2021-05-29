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
use Carbon\Carbon;

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
                    ->leftJoin($this->tbWebinar . ' as webinar', 'order.webinar_id', '=', 'webinar.id')
                    ->where('order.id', '=', $request->order_id)
                    ->get();

                $student = DB::connection('pgsql2')
                    ->table($this->tbStudent)
                    ->where('id', '=', $orderWebinar[0]->student_id)
                    ->get();

                $order_id = "WB003" . $student[0]->id . $request->order_id;

                $transaction_details = array(
                    'order_id' => $order_id,
                    'gross_amount' => $orderWebinar[0]->price,
                );

                $item_details = array([
                    'id' => $request->order_id,
                    'price' => $orderWebinar[0]->price,
                    'name' => $orderWebinar[0]->event_name,
                    'quantity' => 1
                ]);

                $customer_detail = array(
                    'first_name' => $student[0]->name,
                    'last_name' => 'last name',
                    'email' => "gunk.adi15@gmail.com",
                    'phone' => $student[0]->phone
                );

                $params = array(
                    'transaction_details' => $transaction_details,
                    'item_details' => $item_details,
                    'customer_details' => $customer_detail
                );

                $token = Veritrans_Snap::getSnapToken($params);

                DB::table($this->tbOrder)
                    ->where('id', $request->order_id)
                    ->update([
                        'token' => $token,
                        'order_id' => $order_id,
                        'modified' => Carbon::now()
                    ]);

                return $token;
            });

            return $status ? $this->makeJSONResponse(['token' => $status], 200) : $this->makeJSONResponse(['message' => 'failed'], 400);
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

        DB::table($this->tbOrder)
            ->where('order_id', $order_id)
            ->update([
                'status' => $status,
                'modified' => Carbon::now()
            ]);
    }
}
