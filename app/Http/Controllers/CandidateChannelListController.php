<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatModel;
use App\Models\ChatRoomModel;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
// use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CandidateChannelListController extends Controller
{
    //
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
        // $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbSchool = SchoolModel::tableName();
    }
    public function countChat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'user_id' => 'numeric|exists:pgsql2.' . $this->tbStudent . ',user_id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $count = 0;
            $student = DB::connection('pgsql2')->table($this->tbStudent)
                ->where('user_id', '=', $request->user_id)
                ->get();
            $room = DB::table($this->tbRoom)
                ->where('student_id', '=', $student[0]->id)
                ->select('id')
                ->get();
            if (count($room) > 0) {
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
            }
            //     $arraychat[$i] = $count;
            // }
            // $total = array_sum($arraychat);
            $response = array(
                'count' => $count,
            );
            return $this->makeJSONResponse($response, 200);
        }
    }
    public function listChannel(Request $request)
    {
        // echo "a";
        // var_dump($request->search);
        // flow
        // 1. data divalidasi
        // 2. kalau benar di cek 
        $validation = Validator::make($request->all(), [
            'search' => 'string|nullable',
            'page'   => 'numeric|nullable'
        ]);
        if ($validation->fails()) {
            $this->makeJSONResponse(['message' => $validation->errors()], 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $count = 0;
                $next = null;
                $previous = null;
                $search = "";
                $result = [];
                // echo "aaa";
                if ($request->search != null && $request->page != null) {
                    // echo "aaa";
                    $searchLength = preg_replace('/\s+/', '', $request->search);
                    if (strlen($searchLength) > 0) {
                        $search = strtolower($request->search);
                    }
                    $page = 0;
                    $page = $request->page;
                    $n = $page + 1;
                    $p = $page - 1;
                    if ($p < 0) {
                        $p = 1;
                    }
                    $next       = env("PYTHON_URL") . "/candidatelistchannel?page=" . $n . "&search=" . $search;
                    $previous   = env("PYTHON_URL") . "/candidatelistchannel?page=" . $p . "&search=" . $search;
                    //
                    $student = DB::connection('pgsql2')->table($this->tbStudent)
                        ->where('user_id', '=', $request->user_id)
                        ->get();
                    $school = DB::connection('pgsql2')->table($this->tbSchool)
                        ->where('id', '=', $student[0]->school_id)
                        ->whereRaw("lower(name)like '%" . $search . "%'")
                        ->get();
                } else {
                    $student = DB::connection('pgsql2')->table($this->tbStudent)
                        ->where('user_id', '=', $request->user_id)
                        ->get();
                    $school = DB::connection('pgsql2')->table($this->tbSchool)
                        ->where('id', '=', $student[0]->school_id)
                        ->get();
                }
                // echo 'aa';
                $channel = DB::table($this->tbChat, 'chat')
                    ->leftJoin($this->tbRoom . " as room", 'chat.room_chat_id', '=', 'room.id')
                    ->where('room.student_id', '=', $student[0]->id)
                    ->orderBy('chat.send_time', 'desc')
                    ->select('room.id as room_id', 'room.student_id', 'room.school_id', 'chat.room_chat_id', 'chat.id as chat_id', 'chat.sender', 'chat.chat', 'chat.image', 'chat.send_time', 'chat.is_readed', 'room.updated_at', 'room.created')
                    ->get();
                $channelid = DB::table($this->tbRoom)
                    ->where('student_id', '=', $student[0]->id)
                    ->select('id')
                    ->get();
                $count = count($channelid);
                $countchannel = count($channel);
                //baru
                if ($countchannel != null && $count > 0) {
                    for ($i = 0; $i < count($school); $i++) {
                        $result[$i] = array(
                            "id"            => $channel[0]->room_id,
                            "student_id"    => $channel[0]->student_id,
                            "school_id"     => $channel[0]->school_id,
                            "school_name"   => $school[0]->name,
                            "school_photo"  => env("PHYTON_URL") . "/media/" . $school[0]->logo,
                            "school_phone"  => $school[0]->phone,
                            "is_deleted"    => false,
                            "created_at"    => $channel[0]->created,
                            "updated_at"    => $channel[0]->send_time
                        );
                    }
                    $response = array(
                        'count'     => $count,
                        'next'      => $next,
                        'previous'  => $previous,
                        'results'    => $result,
                        // 'channel'   => $channelid,
                        // 'school'    => $school,
                        // 'student'    => $student,
                    );
                    return $response;
                } else {
                    // var_dump($request->search);
                    //

                    $response = array(
                        'count'     => $count,
                        'next'      => $next,
                        'previous'  => $previous,
                        'results'    => $result,
                        'channel'   => $channelid,
                        // 'school'    => $school,
                        // 'student'    => $student,
                        // 'else'         => 'else'
                    );
                    return $response;
                }
            });
            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(['message' => 'error'], 500);
            }
        }
    }
}
