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
            'student_id' => "required|numeric|exists:pgsql2" . $this->tbStudent . ',id',
            'chat' => 'required|string',
            'image' => 'mimes:jpg,jpeg,pdf,png|max:2000',
            // 'link' => 'url'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::table(function () use ($request) {
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
                        'type'          => "chat",
                        'image'         => $path,
                        'sender'        => "student",
                        'send_time'     => $datetime
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
                        ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time')
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
                        'type'          => "chat",
                        'image'         => env("WEBINAR_URL") . $chattable[0]->image,
                        'sender'        => "student",
                        'send_time'     => $chattable[0]->send_time
                    );
                    $response = array_values(array(
                        "room" => $roomResponse,
                        "chat" => $chatResponse,
                    ));
                } else {
                    $lastRoom = DB::table($this->tbRoom)
                        ->where('student_id', '=', $request->student_id)
                        ->where('school_id', '=', $school[0]->school_id)
                        ->get();

                    DB::table($this->tbChat)->insert(array(
                        'room_chat_id'  => $lastRoom[0]->id,
                        'chat'          => $request->chat,
                        'type'          => 'chat',
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
                        ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time')
                        ->get();
                    $arr_length = count($chattable);
                    $chatResponse = array(
                        'chat_id'       => $chattable[$arr_length - 1]->chat_id,
                        'room_chat_id'  => $chattable[$arr_length - 1]->room_chat_id,
                        'chat'          => $chattable[$arr_length - 1]->chat,
                        'type'          => "chat",
                        'image'         => env("WEBINAR_URL") . $chattable[$arr_length - 1]->image,
                        'sender'        => "student",
                        'send_time'     => $chattable[$arr_length - 1]->send_time
                    );
                    $response = array_values(array(
                        "chat" => $chatResponse,
                    ));
                }
                return $this->makeJSONResponse($response, 200);
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
            $data = DB::transaction(function () use ($request) {
                try {
                    $current_page = 1;
                    $query_pagination = "";
                    $query_search = "";

                    $count_chat = DB::select("select count(room_chat_id) from " . $this->tbChat . " group by room_chat_id");

                    $total_page = ceil($count_chat[0]->count / 10);

                    $start_item = 0;
                    $current_page = $request->page;
                    if ($current_page > $total_page) {
                        $current_page = $total_page;
                    }
                    if ($current_page > 1) {
                        $start_item = ($current_page - 1) * 10;
                    }
                    $query_pagination = " limit 10 offset " . $start_item;

                    if ($request->search != null) {
                        $search_length = preg_replace('/\s+/', '', $request->search);
                        if (strlen($search_length) > 0) {
                            $search = strtolower($request->search);
                            $query_search = " and lower(chat.chat) like '%" . $search . "%'";
                        }
                    }

                    $chatting = DB::select("select chat.room_chat_id, chat.id as chat_id, chat.sender, room.student_id, room.school_id, chat.chat, chat.image, chat.send_time from " . $this->tbChat . " as chat left join " . $this->tbRoom . " as room on chat.room_chat_id = room.id where room.student_id = " . $request->student_id . $query_search . " order by chat.id desc" . $query_pagination);
                } catch (Exception $e) {
                    echo $e;
                }
                // if ($chatting > 0) {
                $dbSchool = DB::connection('pgsql2')->table($this->tbSchool)
                    ->where("id", "=", $chatting[0]->school_id)
                    ->get();

                $schooldata = (object) $dbSchool;

                $room = (object) array(
                    "room_chat_id"  => $chatting[0]->room_chat_id,
                    "student_id"    => $chatting[0]->student_id,
                    "school_id"     => $chatting[0]->school_id,

                );
                for ($i = 0; $i < count($chatting); $i++) {
                    $chat[$i] = (object) array(
                        "chat_id"       => $chatting[$i]->chat_id,
                        "sender"        => $chatting[$i]->sender,
                        "chat"          => $chatting[$i]->chat,
                        "image"         => env("WEBINAR_URL") . $chatting[$i]->image,
                        "send_time"     => $chatting[$i]->send_time,
                    );
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
                return count($chatting);
                // } else {
                // $response = "no data!";
                // }
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(["message" => "transcation failed!"], 400);
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
}
