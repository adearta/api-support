<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\StudentCandidateModel;
use App\Models\StudentModel;
use App\Models\StudentParticipantAkbarModel;
// use App\Models\WebinarAkbarModel;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;;

class StudentParticipantAkbarController extends Controller
{
    private $tbStudentParticipants;
    private $tbStudent;

    use ResponseHelper;

    public function __construct()
    {
        $this->tbStudentParticipants = StudentParticipantAkbarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        //$this->tbWebinar = WebinarAkbarModel::tableName();   
    }
    public function getStudent()
    {
        $data = DB::select('select * from career_support_models_student');
        return $this->makeJSONResponse(['data' => $data], 200);
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
    // public function getTotalParticipants($id)
    // {
    //     $count = DB::select('select count (webinar_id) from career_support_models_studentparticipants where webinar_id = ?', [$id]);
    //     return $this->makeJSONResponse(['data' => $count], 200);
    // }
    public function addStudentManual(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'nim' => 'required',
            'class' => 'required',
            'batch' => 'required|numeric',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 202);
        } else {
            try {
                //insert to student
                $student = DB::table($this->tbStudent)->insertGetId([
                    'name' => $request->name,
                    'nim' => $request->nim,
                    'class' => $request->class,
                    'batch' => $request->batch,
                ]);
                foreach ($request->school_id as $s) {
                    foreach ($request->webinar_id as $w) {
                        $count = DB::select('select count (webinar_id) from career_support_models_studentparticipants where webinar_id = ?', [$id]);
                        if ($count < 501) {
                            DB::table($this->tbStudentParticipants)->insert(array(
                                'school_id' => $s,
                                'webinar_id' => $w,
                                'student_id' => $student,
                                //kurang masukin ke notif student 
                                //kurang kirim ke email siswa
                            ));
                        } else {
                            return $this->makeJSONResponse(["message" => "sorry, has reach maximum quota!, 500/500"], 200);
                        }
                    }
                }
            } catch (Exception $e) {
                echo $e;
            }
            return $this->makeJSONResponse(["message" => "Success to save data to database"], 200);
        }
    }
}
