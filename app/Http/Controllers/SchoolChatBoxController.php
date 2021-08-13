<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use App\Models\ChatModel;
use App\Models\ChatRoomModel;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\UserPersonal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Storage;
use Symfony\Contracts\Service\Attribute\Required;

class SchoolChatBoxController extends Controller
{
    use ResponseHelper;
    private $tbChat;
    private $tbRoom;
    private $tbSchool;
    private $tbStudent;
    private $tbNotif;
    private $tbUserPersonal;

    public function __construct()
    {
        $this->tbChat = ChatModel::tableName();
        $this->tbRoom = ChatRoomModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbUserPersonal = UserPersonal::tableName();
    }
    public function createChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id',
            'chat' => 'required|string',
            'image' => 'mimes:jpg,jpeg,pdf,png|max:2000',
            'datetime' => 'date_format:Y-m-d H:i:s'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                date_default_timezone_set("Asia/Jakarta");
                $datetime = date("Y-m-d h:i:sa");
                //make chat room
                $cekRoom = DB::table($this->tbRoom)
                    ->where('school_id', '=', $request->school_id)
                    ->where('student_id', '=', $request->student_id)
                    ->get();

                $count = count($cekRoom);

                $path = null;
                if ($file = $request->file('image')) {
                    $path = $file->store('chat', 'public');
                }
                if ($count < 1) {
                    $chatRoom = DB::table($this->tbRoom)->insertGetId(array(
                        'school_id' => $request->school_id,
                        'student_id' => $request->student_id
                    ));
                } else {
                    $chatRoom = $cekRoom[0]->id;
                }

                // $response_path = null;
                // if ($broadcast->image != null) {
                //     $response_path = env("WEBINAR_URL") . $broadcast->image;
                // }
                DB::table($this->tbChat)->insert(array(
                    'room_chat_id'  => $chatRoom,
                    'chat'          => $request->chat,
                    'image'         => $path,
                    'send_time'     => $datetime,
                    'sender'        => "school",

                ));
                DB::table($this->tbNotif)->insert(array(
                    'student_id'    => $request->student_id,
                    'room_chat_id'  => $chatRoom,
                    'message_id'    => "Anda mendapatkan chat baru dari Admin",
                    'message_en'    => "You've got a new chat from Admin"
                ));

                $chattable = DB::table($this->tbChat, 'chat')
                    ->leftJoin($this->tbRoom . " as room", 'chat.room_chat_id', '=', 'room.id')
                    // ->leftJoin($this->tbStudent . " as std", "std.user_id", "=", "room.student_id")
                    ->where('room.id', '=', $chatRoom)
                    ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed', 'room.updated_at', 'room.updated_at')
                    ->get();
                // if (!empty($chattable)) {
                $student = DB::connection('pgsql2')->table($this->tbStudent, "std")
                    ->leftJoin($this->tbUserPersonal . " as user", 'std.id', '=', 'user.id')
                    ->where('std.id', '=', $request->student_id)
                    ->select('std.phone', 'std.avatar', 'user.first_name', 'user.last_name')
                    ->get();
                $model = StudentModel::find($chattable[0]->student_id);
                $response_path = null;
                if ($model->avatar != null) {
                    $response_path = env("WEBINAR_URL") . $model->avatar;
                }
                $roomResponse = array(
                    // id, school_id, student_id, student_phone, student_photo & updated_at
                    'id'            => $chattable[0]->room_id,
                    'school_id'     => $chattable[0]->school_id,
                    'student_id'    => $chattable[0]->student_id,
                    'student_phone' => $student[0]->phone,
                    "student_name"  => $student[0]->first_name . " " . $student[0]->last_name,
                    'student_photo' => $response_path,
                    'updated_at'    => $chattable[0]->updated_at,
                );
                $chat = DB::table($this->tbChat)
                    ->where('room_chat_id', '=', $chattable[0]->room_id)
                    ->get();

                for ($i = 0; $i < count($chat); $i++) {
                    $chatmodel[$i] = ChatModel::find($chat[$i]->id);
                    $response_path = null;
                    if ($chatmodel[$i]->image != null) {
                        $response_path = env("WEBINAR_URL") . $chatmodel[$i]->image;
                    }
                    $chatResponse[$i] = array(
                        'id'       => $chat[$i]->id,
                        'channel_id'  => $chat[$i]->room_chat_id,
                        'chat'          => $chat[$i]->chat,
                        'image'         => $response_path,
                        'send_time'     => $chat[$i]->send_time,
                        'sender'        => $chat[$i]->sender,
                        'is_readed'     => $chat[$i]->is_readed
                    );
                }
                return (object) array(
                    "channel" => $roomResponse,
                    "list_chat" => $chatResponse,
                );
            });
            if ($data) {
                return $this->makeJSONResponse($data, 201);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
    public function deleteChat($chat_id)
    {
        $validation = Validator::make(["chat_id" => $chat_id], [
            "chat_id" => 'required|numeric|exists:' . $this->tbChat . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 200);
        } else {
            $delete = ChatModel::findOrfail($chat_id);
            if ($delete) {
                if (Storage::disk('public')->exists($delete->image)) {
                    Storage::disk('public')->delete($delete->image);
                }
                $delete->delete();
            }
            return $this->makeJSONResponse(['message' => 'chat deleted'], 200);
        }
    }
    public function listRoom(Request $request)
    {
        /**
         * list-of-room and search by student name
         * response
         *  
         * school_id
         * search
         * 
         * 
         */
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            'search'    => 'nullable|string'

        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            try {
                $data = DB::transaction(function () use ($request) {
                    $current_page = 1;
                    $roomdetail = [];
                    $start_item = 0;


                    $school = DB::connection('pgsql2')->table($this->tbSchool)
                        ->where('id', '=', $request->school_id)
                        ->get();

                    $room_count = DB::table($this->tbRoom)
                        ->selectRaw('count(id)')
                        ->where('school_id', '=', $request->school_id)
                        ->get();

                    $total_page = ceil($room_count[0]->count / 10);

                    if ($request->page != null) {
                        $current_page = $request->page;

                        if ($current_page > 1) {
                            $start_item = ($current_page - 1) * 10;
                        }
                    }
                    $roomdetail = [];
                    if ($current_page > 0 && $current_page <= $total_page) {
                        $search = "";
                        if ($request->search != null) {
                            $searchLength = preg_replace('/\s+/', '', $request->search);
                            if (strlen($searchLength) > 0) {
                                $search = strtolower($request->search);
                            }
                            $channel = DB::table($this->tbRoom)
                                ->where('school_id', '=', $request->school_id)
                                ->select('*')
                                ->orderBy('id', 'asc')
                                ->get();

                            $student = array();
                            $studentIndex = 0;
                            for ($i = 0; $i < count($channel); $i++) {
                                $req[$i] = $channel[$i]->student_id;
                                $data = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                    ->leftJoin($this->tbUserPersonal . ' as personal', 'student.user_id', '=', 'personal.id')
                                    ->where('student.school_id', '=', $req[$i])
                                    ->whereRaw("lower(concat(personal.first_name,' ',personal.last_name)) like '%" . $search . "%'")
                                    ->orderBy('personal.id', 'asc')
                                    ->limit(10)
                                    ->offset($start_item)
                                    ->select('student.*', 'personal.first_name', 'personal.last_name')
                                    ->get();
                                if (count($data) > 0) {
                                    $student[$studentIndex] = $data[0];
                                    $studentIndex++;
                                }
                            }
                            if (count($student) > 0) {
                                for ($i = 0; $i < count($student); $i++) {
                                    $photo = StudentModel::find($student[$i]->id);
                                    $response_path = null;
                                    if ($photo->avatar != null) {
                                        $response_path = env("WEBINAR_URL") . $student[$i]->avatar;
                                    }
                                    $roomdetail[$i] = (object) array(
                                        'id'            => $channel[$i]->id,
                                        "school_id"     => $channel[$i]->school_id,
                                        "student_id"    => $channel[$i]->student_id,
                                        "student_name"  => $student[$i]->first_name . " " . $student[$i]->last_name,
                                        "student_photo" => $response_path,
                                        "student_phone" => $student[$i]->phone,
                                        "is_deleted"    => $channel[$i]->is_deleted,
                                        "updated_at"    => $channel[$i]->updated_at,
                                    );
                                }
                            }

                            // $count =  count($student);
                            // if ($count > 0 || $count != null) {
                            //     for ($i = 0; $i < $count; $i++) {
                            //         $rooms = DB::table($this->tbRoom)
                            //             // ->selectRaw('count(id)')
                            //             ->where('student_id', '=', $student[$i]->id)
                            //             ->get();
                            //         $arrayroom[$i] = $rooms[0];
                            //     }
                            //     $rooms = [];
                            //     // var_dump($arrayroom);
                            //     $roomarray = [];
                            // for ($j = 0; $j < count($arrayroom); $j++) {
                            //     //id, school_id, student_id, student_phone, student_name, student_photo & updated_at
                            //     $room = DB::table($this->tbRoom)
                            //         ->where('school_id', '=', $request->school_id)
                            //         ->where('student_id', '=', $student[$j]->id)
                            //         ->get();
                            //     $roomarray[$j] = $room[0];
                            // }
                            // for ($i = 0; $i < count($roomarray); $i++) {
                            //     $photo = StudentModel::find($student[$i]->id);
                            //     $response_path = null;
                            //     if ($photo->avatar != null) {
                            //         $response_path = env("WEBINAR_URL") . $student[$i]->avatar;
                            //     }
                            //     $roomdetail[$i] = array(
                            //         'id'            => $roomarray[$i]->id,
                            //         "school_id"     => $roomarray[$i]->school_id,
                            //         "student_id"    => $roomarray[$i]->student_id,
                            //         "student_name"  => $student[$i]->first_name . " " . $student[$i]->last_name,
                            //         "student_photo" => $response_path,
                            //         "student_phone" => $student[$i]->phone,
                            //         "is_deleted"    => $roomarray[$i]->is_deleted,
                            //         "updated_at"    => $roomarray[$i]->updated_at,
                            //     );
                            // }
                            // }
                            // }
                        } else {
                            // $channelArr = [];
                            $channel = DB::table($this->tbRoom)
                                ->where('school_id', '=', $request->school_id)
                                ->get();
                            //     $channelArr[$i] = $channel[0];
                            // }
                            $studentArr = [];
                            // for ($j = 0; $j < count($channelArr); $j++) {
                            for ($i = 0; $i < count($channel); $i++) {
                                $students = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                    ->leftJoin($this->tbUserPersonal . ' as personal', 'student.user_id', '=', 'personal.id')
                                    ->where('student.id', '=', $channel[$i]->student_id)
                                    ->orderBy('personal.id', 'desc')
                                    ->limit(10)
                                    ->offset($start_item)
                                    ->select('student.*', 'personal.first_name', 'personal.last_name')
                                    ->get();
                                $studentArr[$i] = $students[0];
                            }
                            // if (count($studentArr) < 1) {
                            //     $roomdetail = [];
                            //     echo "masuk sini";
                            //     var_dump($studentArr);
                            // } else {
                            // var_dump($studentArr);
                            for ($i = 0; $i < count($studentArr); $i++) {
                                $photo = StudentModel::find($studentArr[$i]->id);
                                $response_path = null;
                                if ($photo->avatar != null) {
                                    $response_path = env("WEBINAR_URL") . $studentArr[$i]->avatar;
                                }
                                $roomdetail[$i] = array(
                                    // 'channel' => $channel[$i],
                                    // 'student' => $studentArr[$i]
                                    'id'            => $channel[$i]->id,
                                    "school_id"     => $channel[$i]->school_id,
                                    "student_id"    => $channel[$i]->student_id,
                                    "student_name"  => $studentArr[$i]->first_name . " " . $studentArr[$i]->last_name,
                                    "student_photo" => $response_path,
                                    "student_phone" => $studentArr[$i]->phone,
                                    "is_deleted"    => $channel[$i]->is_deleted,
                                    "updated_at"    => $channel[$i]->updated_at,
                                );
                            }
                        }
                        // }
                    }

                    // $
                    $response = (object)array(
                        'data'   => $roomdetail,
                        'pagination' => (object) array(
                            'current_page'    => (int) 1,
                            'last_page'     => (int) $total_page,
                            'total_data'    => (int) $room_count[0]->count
                        )
                    );

                    return (object)array(
                        'school' => $school[0],
                        'channel' => $response,
                        // 'count' => count($arrayroom)
                    );
                });
                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transaction failed"], 400);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }

    public function listCandidate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'numeric|required',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $detail = DB::table($this->tbRoom)
                    ->where('school_id', '=', $request->school_id)
                    ->get();

                return $detail;
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    public function deleteRoom($channel_id)
    {
        $validation = Validator::make(["channel_id" => $channel_id], [
            'channel_id' => 'required|numeric|exists:' . $this->tbRoom . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $delete = ChatRoomModel::findOrfail($channel_id);
            if ($delete) {
                $delete->delete();
                return $this->makeJSONResponse(["message" => "successfully delete room chat!"], 200);
            }
        }
    }
    public function detailChat($channel_id)
    {
        $validation = Validator::make(["channel_id" => $channel_id], [
            'channel_id' => 'required|numeric|exists:' . $this->tbRoom . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            try {
                $responseChat = [];
                // $data = DB::transaction(function () use ($channel_id) {
                $detail = DB::table($this->tbChat, 'chat')
                    ->leftJoin($this->tbRoom . " as room", 'chat.room_chat_id', '=', 'room.id')
                    ->where('room.id', '=', $channel_id)
                    ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed', 'room.updated_at')
                    ->get();
                $count = count($detail);
                if ($count < 1) {
                    // var_dump($detail);
                    $channel = DB::table($this->tbRoom)
                        ->where('id', '=', $channel_id)
                        ->get();
                    $detailChannel = DB::connection('pgsql2')->table($this->tbStudent, 'std')
                        ->leftJoin($this->tbUserPersonal . " as user", 'std.id', '=', 'user.id')
                        ->where('std.id', '=', $channel[0]->student_id)
                        ->select('std.phone', 'user.first_name', 'user.last_name', 'std.avatar')
                        ->get();
                    $chatmodel = StudentModel::find($channel[0]->student_id);
                    $response_path = null;
                    if ($chatmodel->avatar != null) {
                        $response_path = env("WEBINAR_URL") . $chatmodel->avatar;
                    }
                    $responseChannel = array(
                        'id'            => $channel[0]->id,
                        'school_id'     => $channel[0]->school_id,
                        'student_id'    => $channel[0]->student_id,
                        "student_phone" => $detailChannel[0]->phone,
                        "student_name"  => $detailChannel[0]->first_name . " " . $detailChannel[0]->last_name,
                        "student_photo" => $response_path,
                        "updated_at"     => $channel[0]->updated_at,
                    );
                    $response = array(
                        // 'count' => count($detail),
                        'channel' => $responseChannel,
                        // 'detail' => $channel,
                        'list_chat' => $responseChat
                    );
                    return  $response;
                } else {
                    // $candidates = [];
                    // for($i = 0 ; $i < count($detail); $i++){
                    $candidate = DB::connection('pgsql2')->table($this->tbStudent, 'std')
                        ->leftJoin($this->tbUserPersonal . " as user", 'std.id', '=', 'user.id')
                        ->where('std.id', '=', $detail[0]->student_id)
                        ->select('std.id as student_id', 'std.phone', 'user.first_name', 'user.last_name', 'std.avatar')
                        ->get();
                    $model = StudentModel::find($candidate[0]->student_id);
                    $response_path = null;
                    if ($model->avatar != null) {
                        $response_path = env("WEBINAR_URL") . $model->avatar;
                    }
                    $responseChannel = array(
                        'id'            => $detail[0]->room_id,
                        'school_id'     => $detail[0]->school_id,
                        'student_id'    => $detail[0]->student_id,
                        "student_phone" => $candidate[0]->phone,
                        "student_name"  => $candidate[0]->first_name . " " . $candidate[0]->last_name,
                        "student_photo" => $response_path,
                        "updated_at"     => $detail[0]->updated_at,
                    );
                    for ($i = 0; $i < count($detail); $i++) {
                        $chatmodel[$i] = ChatModel::find($detail[$i]->chat_id);
                        $response_path = null;
                        if ($chatmodel[$i]->image != null) {
                            $response_path = env("WEBINAR_URL") . $chatmodel[$i]->image;
                        }
                        // "id": 10,
                        // "channel_id": 8,
                        // "sender": "student",
                        // "chat": "test message 123",
                        // "image": null,
                        // "send_time": "2021-07-08 23:58:44",
                        // "is_readed": false
                        $responseChat[$i] = array(
                            'id'            => $detail[$i]->chat_id,
                            'channel_id'    => $detail[$i]->room_chat_id,
                            'sender'        => $detail[$i]->sender,
                            'chat'          => $detail[$i]->chat,
                            'image'         => $response_path,
                            'send_time'     => $detail[$i]->send_time,
                            'is_readed'     => $detail[$i]->is_readed
                        );
                    }
                    $response = array(
                        // 'count' => count($detail),
                        'channel' => $responseChannel,
                        'list_chat' => $responseChat
                        // 'detail' => $detail
                    );
                    // $arrayResponse = array_values($response);
                    return  $response;
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    public function listChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            // 'page' => 'numeric',
            'search' => 'string|nullable'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($request) {
                    $candidateResponse = [];
                    $search = "";
                    if ($request->search != null) {
                        $search_length = preg_replace('/\s+/', '', $request->search);
                        if (strlen($search_length) > 0) {
                            $search = strtolower($request->search);
                        }
                        //get student id yang terdapat pada tabel room
                        $channel = DB::table($this->tbRoom)
                            ->where('school_id', '=', $request->school_id)
                            ->select('id', 'student_id')
                            ->orderBy('id', 'asc')
                            ->get();
                        //hitung banyaknya room dengan id school yang sesuai
                        $count = count($channel);
                        //jika ada room
                        $arr = [];
                        $arrchannel = [];
                        $name = array();
                        $nameIndex = 0;
                        // $names = [];
                        //kemudian select id room nya
                        for ($h = 0; $h < $count; $h++) {

                            $stu[$h] = $channel[$h]->student_id;
                            $data = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                ->leftJoin($this->tbUserPersonal . ' as personal', 'student.user_id', '=', 'personal.id')
                                ->where('student.id', '=', $stu[$h])
                                ->whereRaw("lower(concat(personal.first_name,' ',personal.last_name)) like '%" . $search . "%'")
                                ->orderBy('id', 'asc')
                                ->select('student.id', 'student.phone', 'student.nim', 'personal.first_name', 'personal.last_name')
                                ->get();

                            if (count($data) > 0) {
                                $name[$nameIndex] = $data[0];
                                $nameIndex++;
                            }
                        }

                        if (count($name) > 0) {
                            for ($j = 0; $j < count($name); $j++) {
                                $test[$j] = (object) array(
                                    'id'            => $name[$j]->id,
                                    'first_name'    => $name[$j]->first_name,
                                    'last_name'     => $name[$j]->last_name,
                                    'nim'           => $name[$j]->nim,
                                    'phone'         => $name[$j]->phone,
                                    'channel_id'    => $channel[$j]->id,
                                );
                            }

                            $response = array(
                                // 'candidate'     => $test,
                                'candidate'  => $test
                            );
                            return $response;
                            //
                        } else {
                            //masih salah
                            $response = array(
                                'candidate' => $arr,
                            );
                            return $response;
                        }
                    } else {
                        $arr = [];
                        $channelarray = DB::table($this->tbRoom)
                            ->where('school_id', '=', $request->school_id)
                            ->select('id', 'student_id')
                            ->get();
                        if (count($channelarray) < 1) {
                            $response = array(
                                'candidate' => $arr
                            );
                            return $response;
                        } else {
                            $candidateArray = [];
                            for ($i = 0; $i < count($channelarray); $i++) {
                                $candidate = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                    ->leftJoin($this->tbUserPersonal . ' as personal', 'student.user_id', '=', 'personal.id')
                                    ->where('student.id', '=', $channelarray[$i]->student_id)
                                    ->orderBy('personal.id', 'asc')
                                    ->limit(10)
                                    ->select('student.id', 'student.phone', 'student.nim', 'personal.first_name', 'personal.last_name')
                                    ->get();
                                $candidateArray[$i] = $candidate[0];
                            }

                            // $channelarr = [];
                            // $count = count($candidate);
                            for ($j = 0; $j < count($channelarray); $j++) {
                                $candidateResponse[$j] = array(
                                    'id'            => $candidateArray[$j]->id,
                                    'first_name'    => $candidateArray[$j]->first_name,
                                    'last_name'     => $candidateArray[$j]->last_name,
                                    'nim'           => $candidateArray[$j]->nim,
                                    'phone'         => $candidateArray[$j]->phone,
                                    'channel_id'    => $channelarray[$j]->id,
                                );
                            }
                            //id, first_name, last_name, nim, phone, channel_id
                            $response = array(
                                'candidate' => $candidateResponse,
                            );
                            return $response;
                        }
                    }
                });
                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transaction failed !"], 400);
                }
                //if search null
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    public function countChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $total = 0;
            $room = DB::table($this->tbRoom)
                ->where('school_id', '=', $request->school_id)
                ->select('id')
                ->get();
            if (count($room) > 0) {
                $arraychat = [];
                for ($i = 0; $i < count($room); $i++) {
                    $chat = DB::table($this->tbChat)
                        ->where('room_chat_id', '=', $room[$i]->id)
                        ->where('is_readed', '=', false)
                        ->where('sender', '=', 'student')
                        ->select('id')
                        ->get();
                    $count = count($chat);
                    $arraychat[$i] = $count;
                }
                $total = array_sum($arraychat);
            }

            $response = array(
                'count' => $total,
            );
            return $this->makeJSONResponse($response, 200);
        }
    }
    public function setReaded(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $room = DB::table($this->tbRoom)
                ->where('school_id', '=', $request->school_id)
                ->select('id')
                ->get();
            $arraychat = [];
            $arraycount = [];
            for ($i = 0; $i < count($room); $i++) {
                $chat = DB::table($this->tbChat)
                    ->where('room_chat_id', '=', $room[$i]->id)
                    ->where('is_readed', '=', false)
                    ->where('sender', '=', 'student')
                    ->select('id')
                    ->get();
                $count = count($chat);
                $arraychat[$i] = $chat;
                $arraycount[$i] = $count;
                for ($s = 0; $s < count($chat); $s++) {
                    DB::table($this->tbChat)
                        ->where('id', '=', $arraychat[$i][$s]->id)
                        ->update(['is_readed' => true]);
                }
            }
            // for($j = 0; $j<)
            $total = array_sum($arraychat);
            // for ($j = 0; $j < count($room); $j++) {

            // }
            $response = array(
                'status' => "all message have been readed!",
            );
            return $this->makeJSONResponse($response, 200);
        }
    }
}
