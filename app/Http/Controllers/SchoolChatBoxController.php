<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use App\Models\ChatModel;
use App\Models\ChatRoomModel;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Storage;

class SchoolChatBoxController extends Controller
{
    use ResponseHelper;
    private $tbChat;
    private $tbRoom;
    private $tbSchool;
    private $tbStudent;
    private $tbNotif;

    public function __construct()
    {
        $this->tbChat = ChatModel::tableName();
        $this->tbRoom = ChatRoomModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
    }
    public function createChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric',
            'student_id' => 'required',
            'chat' => 'required',
            'image' => 'mimes:jpg,jpeg,pdf,png|max:2000',
            'datetime' => ''
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
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
                'type'          => "chat",
                'image'         => $path,
                'sender'        => "school",
                'send_time'     => $datetime
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
                ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time')
                ->get();
            if ($count < 1) {

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
                    'image'         => url('api/v1/administrator/img/' . $chattable[0]->image),
                    'sender'        => "student",
                    'send_time'     => $chattable[0]->send_time
                );
                $response = array_values(array(
                    "room" => $roomResponse,
                    "chat" => $chatResponse,
                ));
            } else {
                $arr_length = count($chattable);
                $chatResponse = array(
                    'chat_id'       => $chattable[$arr_length - 1]->chat_id,
                    'room_chat_id'  => $chattable[$arr_length - 1]->room_chat_id,
                    'chat'          => $chattable[$arr_length - 1]->chat,
                    'type'          => "chat",
                    'image'         => url('api/v1/administrator/img/' . $chattable[$arr_length - 1]->image),
                    'sender'        => "student",
                    'send_time'     => $chattable[$arr_length - 1]->send_time
                );
                $response = array_values(array(
                    "chat" => $chatResponse,
                ));
            }
            return $this->makeJSONResponse($response, 200);
        }
    }
    public function deleteChat($chat_id)
    {
        $delete = ChatModel::findOrfail($chat_id);
        if ($delete) {
            if (Storage::disk('public')->exists($delete->image)) {
                Storage::disk('public')->delete($delete->image);
                $delete->delete();
            }
        }
        return $this->makeJSONResponse(['message' => 'chat deleted'], 200);
    }
    public function listRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'school_id' => 'required'

        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $listRoom = DB::table($this->tbRoom)
                ->where('school_id', '=', $request->school_id)
                ->get();

            $studentRoom = DB::table($this->tbRoom)
                ->where('school_id', '=', $request->school_id)
                ->get();
            $school = DB::connection('pgsql2')->table($this->tbSchool)
                ->where('id', '=', $request->school_id)
                ->get();
            for ($i = 0; $i < count($studentRoom); $i++) {
                $student = DB::connection('pgsql2')->table($this->tbStudent)
                    ->where('id', '=', $studentRoom[$i]->student_id)
                    ->select('*')
                    ->get();

                for ($j = 0; $j < count($listRoom); $j++) {
                    $arrayRoom[$i] = (object) array(
                        'room'          => $studentRoom[$i],
                        'student'       => $student[0]
                    );

                    $response = array_values(array(
                        'school'        => $school[0],
                        'student_room'  => array_values($arrayRoom)
                        // $arrayRoom,
                    ));
                }
            }
        }
        return $this->makeJSONResponse($response, 200);
    }
    public function deleteRoom($room_chat_id)
    {
        $delete = ChatRoomModel::findOrfail($room_chat_id);
        if ($delete) {
            $delete->delete();
            return $this->makeJSONResponse(["message" => "successfully delete room chat!"], 200);
        }
    }
    //kurang bikin handling kalau 
    public function     listChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_chat_id' => 'required|numeric',
            'page' => 'numeric',
            // 'search' => ''
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $current_page = 1;
                $query_pagination = "";
                $query_search = "";

                $count_chat = DB::select("select count(room_chat_id) from " . $this->tbChat . " where room_chat_id = " . $request->room_chat_id . " group by room_chat_id");
                // if(!empty($count_chat))
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

                $chatting = DB::select("select chat.room_chat_id, chat.id as chat_id, chat.sender, room.student_id, room.school_id, chat.chat, chat.image, chat.send_time from " . $this->tbChat . " as chat left join " . $this->tbRoom . " as room on chat.room_chat_id = room.id where room.id = " . $request->room_chat_id . $query_search . " order by chat.id desc" . $query_pagination);
                $dbStudent = DB::connection('pgsql2')->table($this->tbStudent)
                    ->where("id", "=", $chatting[0]->student_id)
                    ->get();

                $studentdata = (object) $dbStudent;

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
                        "image"         => url('api/v1/administrator/img/' . $chatting[$i]->image),
                        "send_time"     => $chatting[$i]->send_time,
                    );
                }
                $response = array_values(
                    array(
                        "student" => $studentdata[0],
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
                //if search null
            } catch (Exception $e) {
                echo $e;
            }
            return $this->makeJSONResponse($response, 200);
        }
    }
    // public function detailChat($room_chat_id)
    // {
    //     $validation = Validator::make(['room_chat_id' => $room_chat_id], ['room_chat_id' => 'required|numeric']);
    //     if ($validation->fails()) {
    //         return $this->makeJSONResponse($validation->errors(), 400);
    //     } else {
    //         try {
    //             $detail =  DB::table($this->tbRoom, 'room')
    //                 ->leftJoin($this->tbChat . " as chat ", "room.id", "=", "chat.room_chat_id")
    //                 ->where('chat.room_chat_id', '=', $room_chat_id)
    //                 ->get();
    //             if (empty($detail)) {
    //                 return $this->makeJSONResponse(["message" => "no chat history, start a chat!"], 200);
    //             }
    //         } catch (Exception $e) {
    //             echo $e;
    //         }
    //         return $this->makeJSONResponse(["chat" => $detail], 200);
    //     }
    // }
}
