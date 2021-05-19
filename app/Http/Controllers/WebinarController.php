<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebinarCandidateModel;
use Illuminate\Support\Facades\DB;
use App\Traits;

class WebinarController extends Controller
{
    //

    public function getWebinar()
    {
        $webinar =  DB::select('select * from career_support_models_webinar');
        $auth = auth()->user();
        if ($auth) {
            return $this->makeJSONResponse($webinar, 200);
        } else {
            return $this->makeJSONResponse(400);
        }
    }
    public function addWebinar(Request $request)
    {
        //create data
        //create notif
        //panggil sendtoschool

    }
    public function updateWebinar(Request $request, $id)
    {
    }
    public function destroy($id)
    {
        $delete = WebinarCandidateModel::findOrfail($id);
        $auth = auth()->user();
        if ($auth) {
            $delete->delete();
            return $this->makeJSONResponse(200);
        } else {
            return $this->makeJSONResponse(400);
        }
    }
    public function sendToSchool()
    {
        //pilih sekolah
        //kirim ke sekolah pilihan
    }
}
