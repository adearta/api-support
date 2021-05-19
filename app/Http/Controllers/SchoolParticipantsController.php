<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolParticipantsCandidateModel;
use App\Traits\ResponseHelper;

class SchoolParticipantsController extends Controller
{
    //
    use ResponseHelper;

    public function updateSchoolWebinar($id, $status)
    {
        //pilih id yang sama dengan id yang di request
        //kemudian untuk id tersebut lakukan update statusnya
        $data = DB::select('select status from career_support_models_school_participants where school_id = ?', [$id]);
        //case status =2 accepted
        //case status =3 rejected
        if ($data) {
            $update = new SchoolParticipantsCandidateModel();
            $update->status = $status->status;
            $save = auth()->user()->schools()->update($update);
            if ($save) {
                if ($status == 2) {
                    //jika diterima maka counter berjalan

                    //berikan respon json berhasil
                    $message = "undangan diterima";
                    return $this->makeJSONResponse($message, 200);
                } else if ($status == 3) {
                    $message = "undangan dtolak";
                    return $this->makeJSONResponse($message, 200);
                    //jika ditolak maka berikan json message ditolak
                }
                //gagal save update
            } else {
                $message_er = "fail to save!";
                return $this->makeJSONResponse($message_er, 400);
            }
        } else {
            $message_err = "error!";
            return $this->makeJSONResponse($message_err, 400);
        }
    }
}
