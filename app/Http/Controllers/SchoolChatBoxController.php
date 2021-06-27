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
            return $this->makeJSONResponse($validation->errors(), 400);
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
                    ->where('room.id', '=', $chatRoom)
                    ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed')
                    ->get();
                // if ($count < 1) {

                $roomResponse = array(
                    'room_id'       => $chattable[0]->room_id,
                    'school_id'     => $chattable[0]->school_id,
                    'student_id'    => $chattable[0]->student_id
                );
                $chat = DB::table($this->tbChat)
                    ->where('room_chat_id', '=', $chattable[0]->room_id)
                    ->get();
                for ($i = 0; $i < count($chat); $i++) {
                    $chatResponse[$i] = array(
                        'chat_id'       => $chat[$i]->id,
                        'room_chat_id'  => $chat[$i]->room_chat_id,
                        'chat'          => $chat[$i]->chat,
                        'image'         => env("WEBINAR_URL") . $chat[$i]->image,
                        'send_time'     => $chat[$i]->send_time,
                        'sender'        => $chat[$i]->sender,
                        'is_readed'     => $chat[$i]->is_readed
                    );
                }
                $response = array_values(array(
                    "room" => $roomResponse,
                    "chat" => $chatResponse,
                    // "count" => count($chat),
                ));
                // } else {
                //     $arr_length = count($chattable);
                //     $chatResponse = array(
                //         'chat_id'       => $chattable[$arr_length - 1]->chat_id,
                //         'room_chat_id'  => $chattable[$arr_length - 1]->room_chat_id,
                //         'chat'          => $chattable[$arr_length - 1]->chat,
                //         'image'         =>  env("WEBINAR_URL") . $chattable[$arr_length - 1]->image,
                //         'sender'        => $chattable[$arr_length - 1]->sender,
                //         'send_time'     => $chattable[$arr_length - 1]->send_time,
                //         'is_readed'     => $chattable[$arr_length - 1]->is_readed
                //     );
                //     $response = array_values(array(
                //         "chat" => $chatResponse,
                //     ));
                // }
                return $response;
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
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
            return $this->makeJSONResponse($validation->errors(), 400);
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
         * room
         * student
         * last-chat
         * 
         */
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            // 'search'    => 'string'

        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($request) {

                    $data = [];
                    $school = DB::connection('pgsql2')->table($this->tbSchool)
                        ->where('id', '=', $request->school_id)
                        ->get();
                    if ($request->search != null) {
                        $search_length = preg_replace('/\s+/', '', $request->search);
                        if (strlen($search_length) > 0) {
                            $search = strtolower($request->search);
                            $index = 0;
                            //salah

                            $student = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                                ->leftJoin($this->tbUserPersonal . ' as personal', 'student.user_id', '=', 'personal.id')
                                ->where('student.school_id', '=', $request->school_id)
                                ->whereRaw("lower(concat(personal.first_name,' ',personal.last_name)) like '%" . $search . "%'")
                                ->orderBy('personal.id', 'asc')
                                // ->limit(10)
                                ->select('student.id', 'student.phone', 'student.nim', 'student.address', 'student.date_of_birth', 'student.gender', 'student.marital_status', 'student.religion', 'student.employment_status', 'student.description', 'student.avatar', 'student.domicile_id', 'student.user_id', 'student.school_id', 'personal.first_name', 'personal.last_name')
                                ->get();

                            for ($i = 0; $i < count($student); $i++) {
                                $room = DB::table($this->tbRoom)
                                    ->where('school_id', '=', $student[$i]->school_id)
                                    ->where('student_id', '=', $student[$i]->id)
                                    ->get();

                                if (count($room) > 0) {
                                    $chat = DB::table($this->tbChat)
                                        ->where('room_chat_id', '=', $room[0]->id)
                                        ->orderBy('id', 'desc')
                                        ->limit(1)
                                        ->get();

                                    $data[$index] = (object) array(
                                        'room'      => $room[0],
                                        'chat'      => $chat[0],
                                        'student'   => $student[$i]
                                    );
                                    $index++;
                                }
                            }
                            $response = (object) array(
                                'school'    => $school,
                                'rooms'     => $data
                            );
                            return $response;
                        }
                    } else {
                        $response = (object) array(
                            'school'    => $school,
                            'rooms'     => $data
                        );
                        return $response;
                    }
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
            return $this->makeJSONResponse($validation->errors(), 400);
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
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                // $data = DB::transaction(function () use ($channel_id) {
                $detail = DB::table($this->tbChat, 'chat')
                    ->leftJoin($this->tbRoom . " as room", 'chat.room_chat_id', '=', 'room.id')
                    ->where('room.id', '=', $channel_id)
                    ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed')
                    ->get();

                $responseChannel = array(
                    'id'            => $detail[0]->room_id,
                    'student_id'    => $detail[0]->student_id,
                    'school_id'     => $detail[0]->school_id,
                );
                for ($i = 0; $i < count($detail); $i++) {
                    $responseChat[$i] = array(
                        'id'            => $detail[$i]->chat_id,
                        'channel_id'    => $detail[$i]->room_chat_id,
                        'sender'        => $detail[$i]->sender,
                        'chat'          => $detail[$i]->chat,
                        'image'         => env("WEBINAR_URL") . $detail[$i]->image,
                        'send_time'     => $detail[$i]->send_time,
                        'is_readed'     => $detail[$i]->is_readed
                    );
                }
                $response = array(
                    // 'count' => count($detail),
                    'channel' => $responseChannel,
                    'chats' => $responseChat
                    // 'detail' => $detail
                );
                $arrayResponse = array_values($response);
                return  $arrayResponse;
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
            // 'search' => 'string'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($request) {
                    if ($request->search != null) {
                        $search_length = preg_replace('/\s+/', '', $request->search);
                        if (strlen($search_length) > 0) {
                            $search = strtolower($request->search);
                        }
                        $student = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                            ->leftJoin($this->tbUserPersonal . ' as personal', 'student.user_id', '=', 'personal.id')
                            ->where('student.school_id', '=', $request->school_id)
                            ->whereRaw("lower(concat(personal.first_name,' ',personal.last_name)) like '%" . $search . "%'")
                            ->orderBy('personal.id', 'asc')
                            ->limit(10)
                            ->select('student.id', 'student.phone', 'student.nim', 'student.address', 'student.date_of_birth', 'student.gender', 'student.marital_status', 'student.religion', 'student.employment_status', 'student.description', 'student.avatar', 'student.domicile_id', 'student.user_id', 'student.school_id', 'personal.first_name', 'personal.last_name')
                            ->get();
                        // $array = array_values($student);
                        return array(
                            'candidate' => $student,
                        );
                    } else {
                        $response = array(
                            'candidate' => [],
                        );
                        return $response;
                    }
                    // $candidate = DB::connection('pgsql2')->select("select student.id as student_id, student.phone, student.nim, student.address, student.date_of_birth, student.gender, student.marital_status, student.religion, student.employment_status, student.description, student.avatar, student.domicile_id, student.user_id, student.school_id, user.first_name, user.last_name from " . $this->tbStudent . " left join " . $this->tbUserPersonal . " as user on student.user_id = user.id where student.school_id = " . $request->school_id . " and (first_name " . $query_search . " or last_name " . $query_search . ")");

                    // foreach ($student as $c) {
                    //     $room = DB::table($this->tbRoom, 'room')
                    //         ->leftJoin($this->tbChat . ' as chat', 'room.id', '=', 'chat.room_chat_id')
                    //         ->where('room.student_id', '=', $c->id)
                    //         ->select('room.id as room_id, room.student_id, room.school_id, chat.chat, chat.image, chat.is_readed, chat.send_time, chat.sender')
                    //         ->get();
                    // }
                    //perbaiki
                    // $chatting = DB::select("select chat.room_chat_id, chat.id as chat_id, chat.sender, room.student_id, room.school_id, chat.chat, chat.image, chat.is_readed, chat.send_time, chat.sender from " . $this->tbChat . " as chat left join " . $this->tbRoom . " as room on chat.room_chat_id = room.id where room.id = " . $request->channel_id . $query_search . "order by chat.id desc" . $query_pagination);
                    // if (count($chatting) > 0) {
                    //     $dbStudent = DB::connection('pgsql2')->table($this->tbStudent)
                    //         ->where("id", "=", $chatting[0]->student_id)
                    //         ->get();

                    //     $studentdata = (object) $dbStudent;

                    //     $room = (object) array(
                    //         "channel_id"  => $chatting[0]->room_chat_id,
                    //         "student_id"    => $chatting[0]->student_id,
                    //         "school_id"     => $chatting[0]->school_id,

                    //     );
                    //     for ($i = 0; $i < count($chatting); $i++) {
                    //         $chat[$i] = (object) array(
                    //             "chat_id"       => $chatting[$i]->chat_id,
                    //             "chat"          => $chatting[$i]->chat,
                    //             "image"         => env("WEBINAR_URL") . $chatting[$i]->image,
                    //             "send_time"     => $chatting[$i]->send_time,
                    //             "sender"        => $chatting[$i]->sender,
                    //             "is_readed"     => $chatting[$i]->is_readed
                    //         );
                    //     }
                    //     $response = array_values(
                    //         array(
                    //             "student" => $studentdata[0],
                    //             "room" => $room,
                    //             "chat_data" => $chat,
                    //             "pagination" => (object) array(
                    //                 "first_page" => 1,
                    //                 "last_page" => $total_page,
                    //                 "current_page" => $current_page,
                    //                 "current_data" => count($chatting),
                    //                 "total_data" => $count_chat[0]->count
                    //             )
                    //         )
                    //     );
                    //     return $response;
                    // } else {
                    //     $response = array_values(
                    //         array(
                    //             "school" => (object) $chatting,
                    //             "room" => (object) $chatting,
                    //             "chat_data" => (object) $chatting,
                    //             "pagination" => (object) array(
                    //                 "first_page" => 1,
                    //                 "last_page" => $total_page,
                    //                 "current_page" => $current_page,
                    //                 "current_data" => count($chatting),
                    //                 "total_data" => $count_chat[0]->count
                    //             )
                    //         )    
                    //     );

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
}
