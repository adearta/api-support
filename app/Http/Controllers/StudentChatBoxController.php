<?php

namespace App\Http\Controllers;

use App\Models\ChatModel;
use App\Models\ChatRoomModel;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
// use App\Models\SchoolModel;
use App\Models\StudentModel;
use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentChatBoxController extends Controller
{
    use ResponseHelper;
    private $tbChat;
    private $tbRoom;
    private $tbNotif;
    private $tbStudent;
    private $tbSchool;
    //
    public function __construct()
    {
        $this->tbChat = ChatModel::tableName();
        $this->tbRoom = ChatRoomModel::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
    }
    public function createChatStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id',
            'chat' => 'required|string',
            'image' => 'mimes:jpg,jpeg,pdf,png|max:2000',
            // 'link' => 'url'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                //make chat
                date_default_timezone_set("Asia/Jakarta");
                $datetime = date("Y-m-d h:i:sa");
                //make chat room
                $cekRoom = DB::table($this->tbRoom)
                    ->where('student_id', '=', $request->student_id)
                    // ->where('school_id', '=', $request->school_id)
                    ->get();
                $count = count($cekRoom);

                $school = DB::connection('pgsql2')
                    ->table($this->tbStudent)
                    ->where('id', '=', $request->student_id)
                    ->select('school_id')
                    ->get();

                $path = null;
                if ($file = $request->file('image')) {
                    $path = $file->store('chat', 'public');
                }
                if ($count < 1) {
                    $room = DB::table($this->tbRoom)->insertGetId(array(
                        'student_id' => $request->student_id,
                        'school_id' => $school[0]->school_id
                    ));

                    DB::table($this->tbChat)->insert(array(
                        'room_chat_id'  => $room,
                        'chat'          => $request->chat,
                        'image'         => $path,
                        'send_time'     => $datetime,
                        'sender'        => "student",
                    ));
                    //insert to tb notif to inform the school
                    DB::table($this->tbNotif)->insert(array(
                        'school_id'     => $school[0]->school_id,
                        'room_chat_id'  => $room,
                        'message_id'    => "Anda mendapatkan chat baru dari siswa",
                        'message_en'    => "You've got a new chat from student"
                    ));
                    $chattable = DB::table($this->tbChat, 'chat')
                        ->leftJoin($this->tbRoom . " as room", 'chat.room_chat_id', '=', 'room.id')
                        ->where('room.id', '=', $room)
                        ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed')
                        ->get();

                    $roomResponse = array(
                        'room_id'       => $chattable[0]->room_id,
                        'school_id'     => $chattable[0]->school_id,
                        'student_id'    => $chattable[0]->student_id
                    );
                    $chatResponse = array(
                        'chat_id'       => $chattable[0]->chat_id,
                        'room_chat_id'  => $chattable[0]->room_chat_id,
                        'chat'          => $chattable[0]->chat,
                        'image'         => url('api/v1/administrator/img/' . $chattable[0]->image),
                        'send_time'     => $chattable[0]->send_time,
                        'sender'        => "student",
                        'is_readed'     => $chattable[0]->is_readed
                    );
                    $response = array_values(array(
                        "room" => $roomResponse,
                        "chat" => $chatResponse,
                    ));
                    return $response;
                } else {
                    $lastRoom = DB::table($this->tbRoom)
                        ->where('student_id', '=', $request->student_id)
                        ->where('school_id', '=', $school[0]->school_id)
                        ->get();

                    DB::table($this->tbChat)->insert(array(
                        'room_chat_id'  => $lastRoom[0]->id,
                        'chat'          => $request->chat,
                        'image'         => $path,
                        'sender'        => "student",
                        'send_time'     => $datetime
                    ));

                    DB::table($this->tbNotif)->insert(array(
                        'school_id'     => $school[0]->school_id,
                        'room_chat_id'  => $lastRoom[0]->id,
                        'message_id'    => "Anda mendapatkan chat baru dari siswa",
                        'message_en'    => "You've got a new chat from student"
                    ));
                    $chattable = DB::table($this->tbChat, 'chat')
                        ->leftJoin($this->tbRoom . " as room", 'chat.room_chat_id', '=', 'room.id')
                        ->where('room.id', '=', $lastRoom[0]->id)
                        ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed')
                        ->get();
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
                            'chat_id'       => $chattable[$i]->chat_id,
                            'room_chat_id'  => $chattable[$i]->room_chat_id,
                            'chat'          => $chattable[$i]->chat,
                            'image'         => url('api/v1/administrator/img/' . $chattable[$i]->image),
                            'send_time'     => $chattable[$i]->send_time,
                            'sender'        => "student",
                            'is_readed'     => $chattable[$i]->is_readed
                        );
                    }
                    $response = array_values(array(
                        "room" => $roomResponse,
                        "chat" => $chatResponse,
                        // 'count' => count($chattable),
                    ));
                    return $response;
                }
            });

            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed !"], 400);
            }
        }
    }
    public function listOfChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id',
            'page'       => 'numeric',
            // 'search' => ''
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $data = DB::transaction(function () use ($request) {
                    $current_page = 1;
                    $query_pagination = "";
                    $query_search = "";
                    $chatting = [];
                    // $total_page = 0;

                    $count_chat = DB::select("select count(chat.room_chat_id) from " . $this->tbChat . " as chat left join " . $this->tbRoom . " as room on chat.room_chat_id = room.id where room.student_id = " . $request->student_id . " group by room_chat_id");
                    if (empty($count_chat[0])) {
                        $count_chat = 0;
                        $total_page = ceil($count_chat / 10);
                    } else {
                        $count_chat = $count_chat;
                        $total_page = ceil($count_chat[0]->count / 10);
                    }
                    // var_dump($count_chat);
                    // echo "masuk";
                    // var_dump($total_page);
                    $start_item = 0;
                    $current_page = intval($request->page);
                    if ($current_page > 1) {
                        $start_item = ($current_page - 1) * 10;
                    }
                    $query_pagination = " limit 10 offset " . $start_item;

                    if ($current_page <= $total_page) {
                        if ($request->search != null) {

                            $search_length = preg_replace('/\s+/', '', $request->search);
                            if (strlen($search_length) > 0) {
                                $search = strtolower($request->search);
                                $query_search = " and lower(chat.chat) like '%" . $search . "%'";

                                $chatting = DB::select("select chat.id as chat_id, chat.room_chat_id, chat.chat, chat.image, chat.send_time, chat.sender, chat.is_readed, room.student_id, room.school_id from " . $this->tbChat . " as chat left join " . $this->tbRoom . " as room on chat.room_chat_id = room.id where room.student_id = " . $request->student_id . $query_search . " order by chat.id desc" . $query_pagination);
                                // var_dump($chatting);
                                if (count($chatting) > 0) {
                                    $dbSchool = DB::connection('pgsql2')->table($this->tbSchool)
                                        ->where("id", "=", $chatting[0]->school_id)
                                        ->get();

                                    $schooldata = (object) $dbSchool;

                                    $room = (object) array(
                                        "room_chat_id"  => $chatting[0]->room_chat_id,
                                        "student_id"    => $request->student_id,
                                        "school_id"     => $chatting[0]->school_id,

                                    );
                                    for ($i = 0; $i < count($chatting); $i++) {
                                        $chat[$i] = (object) array(
                                            "chat_id"       => $chatting[$i]->chat_id,
                                            "chat"          => $chatting[$i]->chat,
                                            "image"         => env("WEBINAR_URL") . $chatting[$i]->image,
                                            "send_time"     => $chatting[$i]->send_time,
                                            "sender"        => $chatting[$i]->sender,
                                            "is_readed"     => $chatting[$i]->is_readed
                                        );
                                    }
                                }
                                $response = array_values(
                                    array(
                                        "school" => $schooldata[0],
                                        "room" => $room,
                                        "chat_data" => $chat,
                                        "pagination" => (object) array(
                                            "first_page" => 1,
                                            "last_page" => $total_page,
                                            "current_page" => $current_page,
                                            "current_data" => count($chatting),
                                            "total_data" => $count_chat[0]->count
                                        )
                                    )
                                );
                                return $response;
                            }
                        } else {
                            $response = array_values(
                                array(
                                    "school" => (object) $chatting,
                                    "room" => (object) $chatting,
                                    "chat_data" => (object) $chatting,
                                    "pagination" => (object) array(
                                        "first_page" => 1,
                                        "last_page" => $total_page,
                                        "current_page" => $current_page,
                                        "current_data" => count($chatting),
                                        "total_data" => $count_chat
                                    )
                                )
                            );
                            return $response;
                        }
                    }
                });
                if ($data) {
                    return $this->makeJSONResponse($data, 200);
                } else {
                    return $this->makeJSONResponse(["message" => "transcation failed!"], 400);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
    public function deleteChat($chat_id)
    {
        $validation = Validator::make(["chat_id" => $chat_id], [
            'chat_id' => 'numeric|required|exists:' . $this->tbChat . ',id'
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
            return $this->makeJSONResponse(["message" => "chat has been deleted!"], 200);
        }
    }
    public function detailSchool(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $getDetail = DB::connection('pgsql2')->table($this->tbStudent, 'std')
                    ->leftJoin($this->tbSchool . " as sch", 'std.school_id', '=', 'sch.id')
                    ->where('std.id', '=', $request->student_id)
                    ->select("sch.*")
                    ->get();
                if ($getDetail) {
                    return $this->makeJSONResponse($getDetail, 200);
                }
                return $this->makeJSONResponse(["message" => "wrong input!"], 200);
            } catch (Exception $e) {
                echo $e;
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
    public function countChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $room = DB::table($this->tbRoom)
                ->where('student_id', '=', $request->student_id)
                ->select('id')
                ->get();
            // $arraychat = [];
            // for ($i = 0; $i < count($room); $i++) {
            $chat = DB::table($this->tbChat)
                ->where('room_chat_id', '=', $room[0]->id)
                ->where('is_readed', '=', false)
                ->where('sender', '=', 'school')
                ->select('id')
                ->get();
            // for ($i = 0; $i < count($chat); $i++) {
            //     DB::table($this->tbChat)
            //         ->where('id', '=', $chat[$i]->id)
            //         ->update(['is_readed' => true]);
            // }
            $count = count($chat);
            //     $arraychat[$i] = $count;
            // }
            // $total = array_sum($arraychat);
            $response = array(
                'count' => $count,
            );
            return $this->makeJSONResponse($response, 200);
        }
    }
    public function setReaded(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $room = DB::table($this->tbRoom)
                ->where('student_id', '=', $request->student_id)
                ->select('id')
                ->get();
            // $arraychat = [];
            // for ($i = 0; $i < count($room); $i++) {
            $chat = DB::table($this->tbChat)
                ->where('room_chat_id', '=', $room[0]->id)
                ->where('is_readed', '=', false)
                ->where('sender', '=', 'school')
                ->select('id')
                ->get();
            for ($i = 0; $i < count($chat); $i++) {
                DB::table($this->tbChat)
                    ->where('id', '=', $chat[$i]->id)
                    ->update(['is_readed' => true]);
            }
            $response = array(
                'status' => 'all message readed !'
            );
            return $this->makeJSONResponse($response, 200);
        }
    }
}
