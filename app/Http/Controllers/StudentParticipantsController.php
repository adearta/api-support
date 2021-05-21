<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\SchoolParticipantsCandidateModel;
use App\Models\StudentCandidateModel;
// use App\Models\WebinarAkbarModel;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentParticipantsController extends Controller
{
    //
    private $tbStudentParticipants;
    private $tbStudent;
    // private $tbWebinar;
    use ResponseHelper;

    public function __construct()
    {
        $this->tbStudentParticipants = SchoolParticipantsCandidateModel::tableName();
        $this->tbStudent = StudentCandidateModel::tableName();
        //  $this->tbWebinar = WebinarAkbarModel::tableName();   
    }
    public function getStudentYearList($batch)
    {
        $list = DB::select('select * from career_support_models_student where batch = ?', [$batch]);
        $auth = auth()->user();
        if ($auth) {
            return $this->makeJSONResponse(["list of student based on years" => $list], 200);
        } else {
            return $this->makeJSONResponse(["can't get data!" => $list], 202);
        }
    }
    public function addStudentManual(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'nim' => 'required',
            'class' => 'required',
            'batch' => 'required',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 202);
        } else {
            try {
                // $webinarId = DB::table($this->tbWebinar)->insertGetId([
                // ]);
                $student = DB::table($this->tbStudent)->insertGetId([
                    'name' => $request->name,
                    'nim' => $request->nim,
                    'class' => $request->class,
                    'batch' => $request->batch,
                ]);
                foreach ($request->school_id as $s) {
                    foreach ($request->webinar_id as $w) {
                        DB::table($this->tbStudentParticipants)->insert(array(
                            'school_id' => $s,
                            'webinar_id' => $w,
                            'student_id' => $student,
                        ));
                    }
                }
            } catch (Exception $e) {
                echo $e;
            }
            return $this->makeJSONResponse(["message" => "Success to save data to database"], 200);
        }
    }
}
