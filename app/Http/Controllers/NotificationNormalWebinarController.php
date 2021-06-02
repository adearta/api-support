<?php

namespace App\Http\Controllers;

use App\Models\CareerSupportModelsNotificationWebinarnormalModel;
use Exception;
use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NotificationNormalWebinarController extends Controller
{
    //
    // use ResponseHelper;
    // private $tbNotif;

    // public function __construct()
    // {
    //     // $this->tbNotif = CareerSupportModelsNotificationWebinarnormalModel::tableName();
    // }
    // public function getNotification(Request $request)
    // {
    //     $validation = Validator::make($request->all(), [
    //         'student_id' => 'required|numeric',
    //     ]);
    //     if ($validation->fails()) {
    //         return $this->makeJSONResponse($validation->errors(), 400);
    //     } else {
    //         try {
    //             $queryWhere = "";
    //             $querySelectId = "";
    //             $queryLang = "";
    //             if ($request->student_id != null) {
    //                 $querySelectId = ", tbNotif.student_id";
    //                 $queryWhere = "where student_id = " . $request->student_id;
    //             } else {
    //                 $message = "parameter incomplete!";
    //                 $code = 404;
    //             }
    //             if ($request->header('Accept-Language') == 'en') {
    //                 $queryLang = ", tbNotif.message_en as message";
    //             } else {
    //                 $queryLang = ", tbNotif.message_id as message";
    //             }

    //             $message = DB::select("select tbNotif.id, " . $querySelectId . ", tbNotif.created, " . $queryLang . " from " . $this->tbNotif . " as tbNotif " . $queryWhere . " order by desc");
    //             return $this->makeJSONResponse($message, $code);
    //         } catch (Exception $e) {
    //             echo $e;
    //         }
    //     }
    // }
    // public function setNotificationReaded(Request $request)
    // {
    //     $validation = Validator::make($request->all(), [
    //         'notification_id' => 'required|numeric',
    //     ]);
    //     if ($validation->fails()) {
    //         return $this->makeJSONResponse($validation->errors(), 400);
    //     } else {
    //         try {
    //             foreach ($request->notification_id as $notif) {
    //                 DB::table($this->tbNotif)
    //                     ->where('id', '=', $notif)
    //                     ->update(['is_readed' => true]);
    //             }
    //             return $this->makeJSONResponse(['message' => 'Notification status has beed updated'], 200);
    //         } catch (Exception $e) {
    //             echo $e;
    //         }
    //     }
    // }
}
