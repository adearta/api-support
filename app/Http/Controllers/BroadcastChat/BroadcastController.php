<?php

namespace App\Http\Controllers\BroadcastChat;

use App\Models\BroadcastModel;
use App\Models\NotificationModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\UserEducationModel;
use App\Models\UserPersonal;
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
    private $tbSchool;
    private $tbStudent;
    private $tbUserEducation;
    private $tbBroadcast;
    private $tbNotification;
    private $tbUser;

    public function __construct()
    {
        $this->tbSchool = SchoolModel::tableName();
        $this->tbStudent = StudentModel::tableName();
        $this->tbUserEducation = UserEducationModel::tableName();
        $this->tbBroadcast = BroadcastModel::tableName();
        $this->tbNotification = NotificationModel::tableName();
        $this->tbUser = UserPersonal::tableName();
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
            'school_id'         => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            'type'              => 'required|numeric',
            'year'              => 'numeric|exists:pgsql2.' . $this->tbUserEducation . ',start_year',
            'chat'              => 'required|string',
            'image'             => 'mimes:jpg,jpeg,png|max:2000',
            'link'              => 'string',
            'student_id.*'      => 'numeric|exists:pgsql2.' . $this->tbStudent . ',id',
            'send_time'         => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $status = DB::transaction(function () use ($request) {
                $student_list = null;
                $path = null;
                if ($file = $request->file('image')) {
                    $path = $file->store('broadcast', 'public');
                }
                $broadcast_id = DB::table($this->tbBroadcast)
                    ->insertGetId(array(
                        'school_id'         => $request->school_id,
                        'chat'              => $request->chat,
                        'image'             => $path,
                        'link'              => $request->link,
                        'send_time'         => $request->send_time,
                        'type'              => $request->type,
                        'year'              => $request->year,
                    ));

                switch ($request->type) {
                    case 1:
                        $student_list = DB::connection('pgsql2')->table($this->tbStudent)
                            ->where('school_id', '=', $request->school_id)
                            ->select('user_id')
                            ->get();
                        break;
                    case 2:
                        $student_list = DB::connection('pgsql2')->table($this->tbStudent, 'student')
                            ->leftJoin($this->tbUserEducation . ' as education', 'student.nim', '=', 'education.nim')
                            ->where('education.school_id', '=', $request->school_id)
                            ->where('education.start_year', '=', $request->year)
                            ->select('student.user_id')
                            ->get();
                        break;
                    case 3:
                        $studentTemp = "";
                        $index = 0;
                        foreach ($request->student_id as $student) {
                            if ($index == 0) {
                                $studentTemp .= $student;
                            } else {
                                $student .= ", " . $student;
                            }
                            $index++;
                        }

                        $student_list = DB::connection('pgsql2')->table($this->tbStudent)
                            ->whereRaw('id = ANY(ARRAY[' . $studentTemp . '])')
                            ->select('user_id')
                            ->get();
                        break;
                }

                foreach ($student_list as $student) {
                    //send notif to frisidea table
                    DB::connection('pgsql2')->table($this->tbNotification)->insert(array(
                        'title'             => "New Announcement",
                        'description'       => "You got a new announcement from school",
                        'datetime'          => $request->send_time,
                        'is_seen'           => false,
                        'type'              => 5,
                        'type_profile'      => 1,
                        'type_education'    => 1,
                        'user_id'           => $student->user_id,
                        'title_en'          => "New Announcement",
                        'title_ind'         => "Pengumuman Baru",
                        'description_en'    => "You got a new announcement from school",
                        'description_ind'   => "Anda mendapatkan pengumuman baru dari sekolah",
                        'updated'           => Carbon::now(),
                        'broadcast_id'      => $broadcast_id
                    ));
                }

                $broadcast = BroadcastModel::find($broadcast_id);
                $response_path = null;
                if ($broadcast->image != null) {
                    $response_path = env("WEBINAR_URL") . $broadcast->image;
                }

                $broadcastResponse = (object) array(
                    'id'                => $broadcast->id,
                    'chat'              => $broadcast->chat,
                    'image'             => $response_path,
                    'link'              => $broadcast->link,
                    'type'              => $broadcast->type,
                    'year'              => $broadcast->year,
                    'send_time'         => $broadcast->send_time,
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

    public function list(Request $request)
    {
        /*
        Param:
        1. School id
        2. Page -> default(0 or null)
        3. Search -> default(null) -> search by broadcast message
        */
        $validation = Validator::make($request->all(), [
            'school_id' => 'required|numeric|exists:pgsql2.' . $this->tbSchool . ',id',
            'page'      => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $status = DB::transaction(function () use ($request) {
                $current_page = 1;
                $data = [];
                $start_item = 0;

                $broadcast_count = DB::table($this->tbBroadcast)
                    ->selectRaw('count(id)')
                    ->where('school_id', '=', $request->school_id)
                    ->get();
                $total_page = ceil($broadcast_count[0]->count / 10);

                if ($request->page != null && $request->page > 0) {
                    $current_page = $request->page;

                    if ($current_page > 1) {
                        $start_item = ($current_page - 1) * 10;
                    }
                } else {
                    $current_page = 0;
                }

                if ($current_page > 0 && $current_page <= $total_page) {
                    $search = "";
                    if ($request->search != null) {
                        $searchLength = preg_replace('/\s+/', '', $request->search);
                        if (strlen($searchLength) > 0) {
                            $search = strtolower($request->search);
                        }
                    }

                    $broadcast = DB::table($this->tbBroadcast)
                        ->where('school_id', '=', $request->school_id)
                        ->whereRaw("lower(chat) like '%" . $search . "%'")
                        ->orderBy('id', 'desc')
                        ->offset($start_item)
                        ->limit(10)
                        ->get();

                    for ($i = 0; $i < count($broadcast); $i++) {
                        $response_path = null;
                        if ($broadcast[$i]->image != null) {
                            $response_path = env("WEBINAR_URL") . $broadcast[$i]->image;
                        }

                        $broadcastCount = DB::connection('pgsql2')->table($this->tbNotification)
                            ->selectRaw('count(id)')
                            ->where('broadcast_id', '=', $broadcast[$i]->id)
                            ->get();

                        $data[$i] = (object) array(
                            'id'                => $broadcast[$i]->id,
                            'chat'              => $broadcast[$i]->chat,
                            'image'             => $response_path,
                            'link'              => $broadcast[$i]->link,
                            'type'              => $broadcast[$i]->type,
                            'year'              => $broadcast[$i]->year,
                            'send_time'         => $broadcast[$i]->send_time,
                            'total_student'     => $broadcastCount[0]->count
                        );
                    }
                }

                $response = (object)array(
                    'data'   => $data,
                    'pagination' => (object) array(
                        'first_page'    => 1,
                        'last_page'     => $total_page,
                        'current_page'  => $current_page,
                        'current_data'  => count($data), // total data based on filter search and page
                        'total_data'    => $broadcast_count[0]->count
                    )
                );
                return $response;
            });

            if ($status) {
                return $this->makeJSONResponse($status, 200);
            } else {
                return $this->makeJSONResponse('failed', 400);
            }
        }
    }

    public function delete($broadcast_id)
    {
        $validation = Validator::make(['broadcast_id' => $broadcast_id], [
            'broadcast_id' => 'required|numeric|exists:' . $this->tbBroadcast . ',id'
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::transaction(function () use ($broadcast_id) {
                $broadcast = BroadcastModel::find($broadcast_id);

                if ($broadcast->image != null) {
                    if (Storage::disk('public')->exists($broadcast->image)) {
                        Storage::disk('public')->delete($broadcast->image);
                    }
                }

                DB::connection('pgsql2')->table($this->tbNotification)
                    ->where('broadcast_id', '=', $broadcast->id)
                    ->delete();
                $broadcast->delete();
                return true;
            });

            if ($data) {
                return $this->makeJSONResponse(['message => successfully delete the broadcast'], 200);
            } else {
                return $this->makeJSONResponse(['message => failed'], 400);
            }
        }
    }

    public function detail(Request $request)
    {
        /*
        Param:
        1. Room id
        2. Page -> default(0 or null)
        3. Search -> by student name
        */
        $validation = Validator::make($request->all(), [
            'broadcast_id'      => 'required|numeric|exists:' . $this->tbBroadcast . ',id',
            'page'              => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $data = DB::transaction(function () use ($request) {
                $current_page = 1;
                $student = [];
                $start_item = 0;
                $search = "";

                $broadcast = BroadcastModel::find($request->broadcast_id);
                $notification = DB::connection('pgsql2')->table($this->tbNotification)
                    ->where('broadcast_id', '=', $request->broadcast_id)
                    ->select('id')
                    ->get();

                $total_page = ceil(count($notification) / 10);

                if ($request->page != null && $request->page > 0) {
                    $current_page = $request->page;

                    if ($current_page > 1) {
                        $start_item = ($current_page - 1) * 10;
                    }
                } else {
                    $current_page = 0;
                }

                if ($current_page > 0 && $current_page <= $total_page) {
                    if ($request->search != null) {
                        $searchLength = preg_replace('/\s+/', '', $request->search);
                        if (strlen($searchLength) > 0) {
                            $search = strtolower($request->search);
                        }
                    }

                    $student = DB::connection('pgsql2')->table($this->tbNotification, 'notif')
                        ->leftJoin($this->tbStudent . ' as student', 'notif.user_id', '=', 'student.user_id')
                        ->leftJoin($this->tbUser . ' as personal', 'notif.user_id', '=', 'personal.id')
                        ->where('notif.broadcast_id', '=', $broadcast->id)
                        ->whereRaw("lower(concat(personal.first_name,' ',personal.last_name)) like '%" . $search . "%'")
                        ->orderBy('personal.id', 'asc')
                        ->offset($start_item)
                        ->limit(10)
                        ->select('student.*', 'personal.first_name', 'personal.last_name')
                        ->get();
                }

                $listStudent = (object) array(
                    'data'       => $student,
                    'pagination' => (object) array(
                        'first_page'    => 1,
                        'last_page'     => $total_page,
                        'current_page'  => $current_page,
                        'current_data'  => count($student), // total data based on filter search and page
                        'total_data'    => count($notification)
                    )
                );

                $response_path = null;
                if ($broadcast->image != null) {
                    $response_path = env("WEBINAR_URL") . $broadcast->image;
                }

                return (object) array(
                    'id' => $broadcast->id,
                    'chat'              => $broadcast->chat,
                    'image'             => $response_path,
                    'link'              => $broadcast->link,
                    'type'              => $broadcast->type,
                    'year'              => $broadcast->year,
                    'student'           => $listStudent
                );
            });

            if ($data) {
                return $this->makeJSONResponse($data, 200);
            } else {
                return $this->makeJSONResponse(['message => failed'], 400);
            }
        }
    }
}
