<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\StudentCandidateModel;
use App\Models\StudentModel;
use App\Models\StudentParticipantAkbarModel;
use App\Models\UserEducationModel;
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

    //all of this student function are used by role = school not by student
    public function __construct()
    {
        $this->tbStudentParticipants = StudentParticipantAkbarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
    }
    public function getStudent()
    {
        $data = DB::connection('pgsql2')->select('select * from' . $this->tbStudent);
        return $this->makeJSONResponse(['data' => $data], 200);
    }
    //getting student based on their batch
    public function getStudentYearList($start_year)
    {
        // $batch = new UserEducationModel();
        // $batch->setConnection('pgsql2');
        // $find = $batch->find(1);
        $list = DB::connection('pgsql2')->select('select * from career_support_models_usereducation where batch = ?', [$start_year]);
        $auth = auth()->user();
        if ($auth) {
            return $this->makeJSONResponse(["list of student based on batch" => $list], 200);
        } else {
            return $this->makeJSONResponse(["can't get data!" => $list], 202);
        }
    }
    //school add the student manually if they not yet registered, also register it to webinar by webinar id
    //if the webinar is full it will return response sorry, has reach maximum quota!, 500/500,
    public function addStudentParticipants(Request $request, $id, $batch, $webinar)
    {
        // //masukkan ke student participnats
        // $data = array();
        // $data = ' insert into career_support_models_studentparticipantakbar (student_id, webinar_id, school_id, creator_id, modifier_id, is_deleted, created, modified) select id, school_id, creator_id, modifier_id, is_deleted, created, modified from public.career_support_models_student where batch = ? and school_id = ?';
        //selecting student id by batch and school
        $idstudent = DB::select('select id from career_support_models_student where school_id = ? and batch = ?', [$id, $batch]);
        // $student = DB::raw($data,[$id,$batch]);
        //inserting student to student participants table
        $participants = DB::table($this->tbStudentParticipants)->insert(array(
            'school_id' => $id,
            'webinar_id' => $webinar,
            'student_id' => array($idstudent),
        ));
        //kirim email ke masig masing student
        //kirim notif ke masing2 student
        return $this->makeJSONResponse(['data' => $participants], 200);
    }
    // public function addStudentManual(Request $request, $id)
    // {
    //     $validation = Validator::make($request->all(), [
    //         'name' => 'required',
    //         'nim' => 'required',
    //         'class' => 'required',
    //         'batch' => 'required|numeric',
    //         'year' => 'require|numeric',
    //     ]);
    //     if ($validation->fails()) {
    //         return $this->makeJSONResponse($validation->errors(), 202);
    //     } else {
    //         try {
    //             //insert to student
    //             $student = DB::table($this->tbStudent)->insertGetId([
    //                 'name' => $request->name,
    //                 'nim' => $request->nim,
    //                 'class' => $request->class,
    //                 'batch' => $request->batch,
    //                 'year' => $request->year,
    //             ]);
    //             // if ($request->school_id != null) {
    //             // $queryWhere = "";
    //             // $queryLanguage = "";
    //             // $querySelectId = "";
    //             foreach ($request->school_id as $s) {
    //                 foreach ($request->webinar_id as $w) {
    //                     $count = DB::select('select count (webinar_id) from career_support_models_studentparticipants where webinar_id = ?', [$id]);
    //                     if ($count < 501) {
    //                         DB::table($this->tbStudentParticipants)->insert(array(
    //                             'school_id' => $s,
    //                             'webinar_id' => $w,
    //                             'student_id' => $student,
    //                         ));
    //                         //push notification to db notification based on id

    //                         //kurang kirim ke email siswa
    //                     } else {
    //                         return $this->makeJSONResponse(["message" => "sorry, has reach maximum quota!, 500/500"], 200);
    //                     }
    //                     // }
    //                 }
    //             }
    //         } catch (Exception $e) {
    //             echo $e;
    //         }
    // return $this->makeJSONResponse(["message" => "Success to save data to database and send notif"], 200);
    // }
    // }
}
