<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use App\Models\NotificationWebinarModel;
use Exception;
use Throwable;

class NotificationWebinarController extends Controller
{
    use ResponseHelper;

    private $tbNotification;

    public function __construct()
    {
        $this->tbNotification = NotificationWebinarModel::tableName();
    }

    public function getNotification(Request $request)
    {
        /* get param
            school_id
            student_id
            accept-language
        */
        try {
            $queryWhere = "";
            $queryLanguage = "";
            $querySelectId = "";

            if ($request->school_id != null) {
                $querySelectId = ", tbNotif.school_id";
                $queryWhere = " where school_id = " . $request->school_id;
            } else {
                $querySelectId = ", tbNotif.student_id";
                $queryWhere = " where student_id = " . $request->student_id;
            }

            if ($request->header('Accept-Language') == "en") {
                $queryLanguage = ", tbNotif.message_en as message";
            } else {
                $queryLanguage = ", tbNotif.message_id as message";
            }

            $getnotif = DB::select("select tbNotif.id " . $querySelectId . $queryLanguage . ", tbNotif.created from " . $this->tbNotification . " as tbNotif " . $queryWhere . " order by id desc");

            return $this->makeJSONResponse($getnotif, 200);
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function setNotificationReaded(Request $request)
    {
        try {
            foreach ($request->notification_id as $notif) {
                DB::table($this->tbNotification)
                    ->where('id', '=', $notif)
                    ->update(['is_readed' => true]);
            }

            return $this->makeJSONResponse(['message' => 'Notification status has beed updated'], 200);
        } catch (Exception $e) {
            echo $e;
        }
    }
}
