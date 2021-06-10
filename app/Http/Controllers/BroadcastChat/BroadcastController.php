<?php

namespace App\Http\Controllers\BroadcastChat;

use App\Models\BroadcastRoomModel;
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
use Illuminate\Support\Facades\Storage;

class BroadcastController extends Controller
{
    use ResponseHelper;
    private $tbRoomChat;
    private $tbRoomBroadcast;
    private $tbChat;
    private $tbSchool;
    private $tbStudent;
    private $tbUserEducation;
    private $tbNotification;

    public function __construct()
    {
        $this->tbRoomChat = ChatRoomModel::tableName();
        $this->tbRoomBroadcast = BroadcastRoomModel::tableName();
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
            'school_id'         => 'required|numeric',
            'broadcast_type'    => 'required|numeric',
            'year'              => 'numeric',
            'chat'              => 'required|string',
            'image'             => 'mimes:jpg,jpeg,png|max:2000',
            'link'              => 'string',
            'student_id.*'      => 'numeric',
            'send_time'         => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $status = DB::transaction(function () use ($request) {
                $student_list = null;
                $broadcast_id = DB::table($this->tbRoomBroadcast)
                    ->insertGetId(array(
                        'school_id'         => $request->school_id,
                        'broadcast_type'    => $request->broadcast_type,
                        'year'              => $request->year
                    ));

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
                        $studentTemp = [];
                        $index = 0;
                        foreach ($request->student_id as $student) {
                            $studentTemp[$index] = (object) array(
                                'student_id'    => $student
                            );
                            $index++;
                        }
                        $student_list = $studentTemp;
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
                        'room_broadcast_id' => $broadcast_id,
                        'chat'              => $request->chat,
                        'image'             => $path,
                        'link'              => $request->link,
                        'type'              => 'broadcast',
                        'sender'            => 'school',
                        'send_time'         => $request->send_time,
                    ));

                    DB::table($this->tbNotification)->insert(array(
                        'student_id'    => $student->student_id,
                        'room_chat_id'  => $room_id,
                        'message_id'    => 'Anda menerima pesan baru dari sekolah',
                        'message_en'    => 'You received a new message from school',
                    ));
                }

                $broadcast = DB::select('select distinct on (broadcast.room_broadcast_id) room.id, broadcast.id as chat_id, broadcast.chat, broadcast.image, broadcast.link, room.year from ' . $this->tbRoomBroadcast . ' as room left join ' . $this->tbChat . ' as broadcast on room.id = broadcast.room_broadcast_id where room.id = ' . $broadcast_id);

                $broadcastResponse = (object) array(
                    'room_broadcast_id' => $broadcast[0]->id,
                    'chat_id'           => $broadcast[0]->chat_id,
                    'chat'              => $broadcast[0]->chat,
                    'image'             => url('api/v1/administrator/img/' . $broadcast[0]->image),
                    'link'              => $broadcast[0]->link,
                    'year'              => $broadcast[0]->year,
                    'total_student'     => count($student_list)
                );

                return $broadcastResponse;
            });

            if ($status) {
                return $this->makeJSONResponse($status, 200);
            } else {
                return $this->makeJSONResponse(['message' => 'failed'], 400);
            }
        }
    }
}
