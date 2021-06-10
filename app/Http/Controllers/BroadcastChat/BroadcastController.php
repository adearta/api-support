<?php

namespace App\Http\Controllers\BroadcastChat;

use App\Models\ChatModel;
use App\Models\ChatRoomModel;
use App\Models\NotificationWebinarModel;
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
    private $tbNotification;

    public function __construct()
    {
        $this->tbRoomChat = ChatRoomModel::tableName();
        $this->tbChat = ChatModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbUserEducation = UserEducationModel::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
    }

    public function create(Request $request)
    {
        /*
            broadcast_type
            1 -> all 
            2 -> by year 
            3 -> specific student
        */
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
            $status = DB::transaction(function () use ($request) {
                $student_list = null;
                switch ($request->broadcast_type) {
                    case 1:
                        $student_list = DB::connection('pgsql2')->table($this->tbStudent)
                            ->where('school_id', '=', $request->school_id)
                            ->select('id as student_id')
                            ->get();
                        break;
                    case 2:
                        $student_list = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                            ->leftJoin($this->tbUserEducation . ' as education', 'student.nim', '=', 'education.nim')
                            ->where('education.school_id', '=', $request->school_id)
                            ->where('education.start_year', '=', $request->year)
                            ->select('student.id as student_id')
                            ->get();
                        break;
                    case 3:
                        $student_list = $request->student_id;
                        break;
                }

                foreach ($student_list as $student) {
                    $room = DB::table($this->tbRoomChat)
                        ->where('school_id', $request->school_id)
                        ->where('student_id', $student->student_id)
                        ->get();

                    $room_id = 0;
                    if (count($room) == 0) {
                        $room_id = DB::table($this->tbRoomChat)
                            ->insertGetId(array(
                                'school_id'     => $request->school_id,
                                'student_id'    => $student->student_id
                            ));
                    } else {
                        $room_id = $room[0]->id;
                    }

                    $path = null;
                    if ($file = $request->file('image')) {
                        $path = $file->store('broadcast', 'public');
                    }

                    DB::table($this->tbChat)->insert(array(
                        'room_chat_id'      => $room_id,
                        'chat'              => $request->chat,
                        'image'             => $path,
                        'link'              => $request->link,
                        'type'              => 'broadcast',
                        'send_time'         => $request->send_time,
                        'broadcast_type'    => $request->broadcast_type,
                        'year'              => $request->year
                    ));

                    DB::table($this->tbNotification)->insert(array(
                        'student_id'    => $student->student_id,
                        'room_chat_id'  => $room_id,
                        'message_id'    => 'Anda menerima pesan baru dari sekolah',
                        'message_en'    => 'You received a new message from school',
                    ));
                }

                $data = (object) array(
                    'broadcast_id'
                );

                return;
            });
        }
    }
}
