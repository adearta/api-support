<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use App\Models\ChatModel;
use App\Models\ChatRoomModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class SchoolChatBoxController extends Controller
{
    use ResponseHelper;
    private $tbChat;
    private $tbRoom;
    // private $tbNotif;
    //
    public function __construct()
    {
        $this->tbChat = ChatModel::tableName();
        $this->tbRoom = ChatRoomModel::tableName();
        // $this->tbNotif = NotificationWebinarModel::tableName();
    }
    public function listChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'student_id' => 'required|numeric',

        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $list = DB::table($this->tbRoom)
                    ->where('student_id', '=', $request->student_id)
                    ->get();
                if (empty($list)) {
                    return $this->makeJSONResponse("no chat, the chat is empty. start a chat!", 200);
                }
            } catch (Exception $e) {
                echo $e;
            }
            return $this->makeJSONResponse($list, 200);
        }
    }
    public function detailChat($room_chat_id)
    {
        $validation = Validator::make(['room_chat_id' => $room_chat_id], ['room_chat_id' => 'required|numeric']);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                $detail =  DB::table($this->tbRoom, 'room')
                    ->leftJoin($this->tbChat . " as chat ", "room.id", "=", "chat.room_chat_id")
                    ->where('chat.room_chat_id', '=', $room_chat_id)
                    ->get();
                if (empty($detail)) {
                    return $this->makeJSONResponse(["message" => "no chat history, start a chat!"], 200);
                }
            } catch (Exception $e) {
                echo $e;
            }
            return $this->makeJSONResponse(["chat" => $detail], 200);
        }
    }
}
