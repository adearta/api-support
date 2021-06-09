<?php

namespace App\Http\Controllers\BroadcastChat;

use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\UserEducationModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use Carbon\Carbon;
use Exception;

class BroadcastController extends Controller
{
    use ResponseHelper;
    private $tbRoomChat;
    private $tbChat;
    private $tbSchool;
    private $tbStudent;
    private $tbUserEducation;

    public function __construct()
    {
        $this->tbRoomChat = StudentModel::tableName();
        $this->tbChat = StudentModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbUserEducation = UserEducationModel::tableName();
    }

    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id'         => 'required|numeric|exists:$tbSchool,id',
            'broadcast_type'    => 'required|numeric',
            'year'              => 'numeric|exists:$tbUserEducation,start_year',
            'chat'              => 'string',
            'image'             => 'mimes:jpg,jpeg,png|max:2000',
            'link'              => 'string',
            'student_id.*'      => 'numberic|exists:$tbStudent,id'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
        }
    }
}
