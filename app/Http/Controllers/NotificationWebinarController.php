<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use Exception;
use Illuminate\Support\Facades\Validator;

class NotificationWebinarController extends Controller
{
    use ResponseHelper;

    private $tbNotification;
    private $tbSchool;
    private $tbStudent;

    public function __construct()
    {
        $this->tbNotification = NotificationWebinarModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudent = StudentModel::tableName();
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

            $validation = Validator::make(['Accept-Language' => $request->header('Accept-Language')], [
                'Accept-Language' => 'required'
            ]);
            if ($validation->fails()) {
                return $this->makeJSONResponse($validation->errors(), 400);
            } else {
                if ($request->school_id != null && $request->student_id == null) {
                    $validation = Validator::make($request->all(), [
                        'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        $querySelectId = ", tbNotif.school_id";
                        $queryWhere = " where school_id = " . $request->school_id;
                    }
                } else if ($request->student_id != null && $request->school_id == null) {
                    $validation = Validator::make($request->all(), [
                        'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id',
                    ]);
                    if ($validation->fails()) {
                        return $this->makeJSONResponse($validation->errors(), 400);
                    } else {
                        $querySelectId = ", tbNotif.student_id";
                        $queryWhere = " where student_id = " . $request->student_id;
                    }
                } else {
                    return $this->makeJSONResponse(['message' => 'choose only student_id or school_id!'], 400);
                }


                if ($request->header('Accept-Language') == "en") {

                    $queryLanguage = ", tbNotif.message_en as message";
                } else if ($request->header('Accept-Language') == "id") {
                    $queryLanguage = ", tbNotif.message_id as message";
                } else {
                    return $this->makeJSONResponse(['message' => 'only can choose language between indonesia(id) or english(en)!'], 400);
                }
            }

            $getnotif = DB::select("select tbNotif.id " . $querySelectId . $queryLanguage . ", tbNotif.created from " . $this->tbNotification . " as tbNotif " . $queryWhere . " order by id desc");

            return $this->makeJSONResponse($getnotif, 200);
        } catch (Exception $e) {
            echo $e;
        }
    }

    public function setNotificationReaded(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|numeric|exists:' . $this->tbNotification . ',id'
        ]);
        if ($validator->fails()) {
            return $this->makeJSONResponse($validator->errors(), 400);
        } else {
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
}
