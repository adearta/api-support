<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use Exception;
use Throwable;

class NotificationController extends Controller
{
    //
    use ResponseHelper;

    public function getNotification($id)
    {
        try {
            $getnotif = DB::select('select * from career_support_models_notifications where school_id = ?', [$id]);
            $auth = auth()->user();
            if ($auth) {
                return $this->makeJSONResponse($getnotif, 200);
            } else {
                $message_err = "no data!";
                return $this->makeJSONResponse($message_err, 400);
            }
        } catch (Exception $e) {
            $message_err = "no data!";
            return $e;
        }
    }
}
