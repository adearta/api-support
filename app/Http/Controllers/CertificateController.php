<?php

namespace App\Http\Controllers;

use App\Jobs\CertificateJob;
use App\Jobs\CertificateAkbarJob;
use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\StudentModel;
use App\Models\CareerSupportModelsCertificate;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\CareerSupportModelsWebinarBiasa;
use App\Models\NotificationWebinarModel;
use App\Models\SchoolModel;
use App\Models\SchoolParticipantAkbarModel;
use App\Models\StudentParticipantAkbarModel;
use App\Models\UserPersonal;
use App\Models\WebinarAkbarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    private $tbStudent;
    private $tbParticipant;
    private $tbCertficate;
    private $tbOrder;
    private $tbWebinar;
    private $tbNotification;
    private $tbWebinarakbar;
    private $tbParticipantakbar;
    private $tbSchool;
    private $tbSch;
    private $tbUserPersonal;
    use ResponseHelper;

    public function __construct()
    {
        $this->tbStudent = StudentModel::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbCertficate = CareerSupportModelsCertificate::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbNotification = NotificationWebinarModel::tableName();
        $this->tbWebinarakbar = WebinarAkbarModel::tableName();
        $this->tbParticipantakbar = StudentParticipantAkbarModel::tableName();
        $this->tbSchool = SchoolParticipantAkbarModel::tableName();
        $this->tbSch = SchoolModel::tableName();
        $this->tbUserPersonal = UserPersonal::tableName();
    }

    public function addCertificateAkbar(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinarakbar . ',id',
            'certificate.*' => 'required|mimes:jpg,jpeg,png|max:2000',
        ]);

        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $validStudent = true;
            $invalidNim = [];
            $indexNim = 0;
            foreach ($request->certificate as $certi) {
                $nim = explode("_", $certi->getClientOriginalName());
                $getStudent = DB::connection('pgsql2')->table($this->tbStudent)
                    ->where('nim', '=', $nim[0])
                    ->select('id')
                    ->get();

                if (count($getStudent) > 0) {
                    $cekRegistered = DB::table($this->tbParticipantakbar)
                        ->where('webinar_id', '=', $request->webinar_id)
                        ->where('student_id', '=', $getStudent[0]->id)
                        ->get();

                    if (count($cekRegistered) == 0) {
                        $validStudent = false;
                        $invalidNim[$indexNim] = (object) array(
                            'nim'   => $nim[0]
                        );
                        $indexNim++;
                    }
                } else {
                    $validStudent = false;
                    $invalidNim[$indexNim] = (object) array(
                        'nim'   => $nim[0]
                    );
                    $indexNim++;
                }
            }

            //invalid student
            if (!$validStudent) {
                $nimList = "";
                for ($i = 0; $i < count($invalidNim); $i++) {
                    if ($i == 0) {
                        $nimList = $invalidNim[$i]->nim;
                    } else {
                        $nimList .= ", " . $invalidNim[$i]->nim;
                    }
                }
                $message = 'Student with the nim ' . $nimList . ' is not registered to this webinar, please re-upload all of the certificates, and please upload certificate with the correct nim!';
                return $this->makeJSONResponse(['message' => $message], 400);
            } else {
                //     //valid student
                //     return $this->makeJSONResponse(['message' => 'validation success'], 200);
                // }
                try {
                    $data = DB::transaction(function () use ($request) {
                        if ($request->hasFile('certificate')) {
                            $certificateAll = $request->file('certificate');
                            foreach (array_slice($certificateAll, 0, 10) as $certi) {
                                $name = $certi->getClientOriginalName();
                                $nim = explode("_", $name);
                                $validationNim = Validator::make(["nim" => $nim[0]], [
                                    "nim" => "numeric|exists:pgsql2." . $this->tbStudent . ",nim"
                                ]);
                                if ($validationNim->fails()) {
                                    return $this->makeJSONResponse($validationNim->errors(), 404);
                                } else {
                                    // $studentId = DB::connection('pgsql2')
                                    //     ->table($this->tbStudent)
                                    //     ->where("nim", "=", $nim[0])
                                    //     ->select("id as student_id", "email", "name", "school_id")
                                    //     ->get();
                                    $studentId = DB::connection('pgsql2')->table($this->tbUserPersonal, 'user')
                                        ->leftJoin($this->tbStudent . ' as student', 'user.id', '=', 'student.user_id')
                                        ->where("student.nim", "=", $nim[0])
                                        ->select('student.id as student_id', 'user.email', 'user.first_name', 'user.last_name', 'student.school_id', 'student.nim')
                                        ->get();

                                    $participantId = DB::table($this->tbParticipantakbar)
                                        ->where("student_id", "=", $studentId[0]->student_id)
                                        ->select("id as participant_id", "webinar_id")
                                        ->get();

                                    if (count($participantId) > 0) {
                                        $webinar = DB::table($this->tbWebinarakbar)
                                            ->where('id', '=', $request->webinar_id)
                                            ->select('*')
                                            ->get();

                                        $school = DB::table($this->tbSchool)
                                            ->where('webinar_id', '=', $request->webinar_id)
                                            ->where('school_id', '=', $studentId[0]->school_id)
                                            ->select('status')
                                            ->get();

                                        if ($school[0]->status == "5") {
                                            $path = $certi->store('certificate_akbar', 'public');
                                            $data =  array(
                                                'certificate' => $path,
                                                'webinar_akbar_id' => $participantId[0]->webinar_id,
                                                'participant_akbar_id' => $participantId[0]->participant_id,
                                                'file_name' => $name,
                                            );

                                            $notif = array(
                                                'student_id'     => $studentId[0]->student_id,
                                                'webinar_akbar_id' => $participantId[0]->webinar_id,
                                                'message_id'    => "Selamat Anda telah mengikuti " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " sertifikat anda telah kami kirimkan ke alamat email anda " . $studentId[0]->email,
                                                'message_en'    => "Congratulation you have attended " . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " your certificae had been sent to your email" . $studentId[0]->email
                                            );

                                            try {
                                                CertificateAkbarJob::dispatch($webinar, $studentId, $data);
                                                DB::table($this->tbCertficate)->insert($data);
                                                DB::table($this->tbNotification)->insert($notif);
                                            } catch (Exception $e) {
                                                echo $e;
                                            }
                                        } else {
                                            return $response = (object) array(
                                                'message' => "cannot save, order status not sucess school_id"
                                            );
                                        }
                                    } else {
                                        return $response = (object) array(
                                            'message' => "The student with the nim and name " . $name . " not registered to this webinar"
                                        );
                                    }
                                }
                            }
                            //response
                            $webinar = DB::table($this->tbWebinarakbar)
                                ->where('id', '=', $request->webinar_id)
                                ->select('*')
                                ->get();

                            $detail = DB::select("select * from " . $this->tbWebinarakbar . " as web left join " . $this->tbSchool . " as school on school.webinar_id = web.id where web.id = " . $request->webinar_id);

                            for ($i = 0; $i < count($detail); $i++) {
                                $temp = DB::connection('pgsql2')->table($this->tbSch)
                                    ->where('id', '=', $detail[$i]->school_id)
                                    ->get();

                                $schoolId[$i] = $temp[0];
                            }
                            $unique = array_values(array_unique($schoolId, SORT_REGULAR));
                            $response = (object) array(
                                "id"   => $webinar[0]->id,
                                "event_name" => $webinar[0]->event_name,
                                "event_date" => $webinar[0]->event_date,
                                "event_time" => $webinar[0]->event_time,
                                "event_picture" => env("WEBINAR_URL") . $webinar[0]->event_picture,
                                "schools"    => $unique,
                                "zoom_link" => $webinar[0]->zoom_link,
                                "is_certificate" => true,
                                "certificate" => "link not found",
                            );

                            return $response;
                        }
                        // }
                    });
                    if ($data) {
                        return $this->makeJSONResponse($data, 200);
                    } else {
                        return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
                    }
                } catch (Exception $e) {
                    echo $e;
                }
            }
        }
    }
    public function zipTest()
    {
        $zip_file = 'webinar_akbar.zip';
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $path = storage_path('app/public/certificate_akbar');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $file) {
            // We're skipping all subfolders
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();

                // extracting filename with substr/strlen
                $relativePath = 'webinar_akbar/' . substr($filePath, strlen($path) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return response()->download($zip_file);
    }
    //internal
    public function addCertificate(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'webinar_id'    => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'certificate.*' => 'required|mimes:jpg,jpeg,png|max:2000',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse($validation->errors(), 400);
        } else {
            $validStudent = true;
            $invalidNim = [];
            $indexNim = 0;
            foreach ($request->certificate as $certi) {
                $nim = explode("_", $certi->getClientOriginalName());
                $getStudent = DB::connection('pgsql2')->table($this->tbStudent)
                    ->where('nim', '=', $nim[0])
                    ->select('id')
                    ->get();

                if (count($getStudent) > 0) {
                    $cekRegistered = DB::table($this->tbParticipant)
                        ->where('webinar_id', '=', $request->webinar_id)
                        ->where('student_id', '=', $getStudent[0]->id)
                        ->get();

                    if (count($cekRegistered) > 0) {
                        $cekOrder = DB::table($this->tbOrder)
                            ->where('participant_id', '=', $cekRegistered[0]->id)
                            ->where('webinar_id', '=', $cekRegistered[0]->webinar_id)
                            ->where('status', '=', 'success')
                            ->get();
                    }


                    if (count($cekOrder) == 0) {
                        $validStudent = false;
                        $invalidNim[$indexNim] = (object) array(
                            'nim'   => $nim[0]
                        );
                        $indexNim++;
                    }
                } else {
                    $validStudent = false;
                    $invalidNim[$indexNim] = (object) array(
                        'nim'   => $nim[0]
                    );
                    $indexNim++;
                }
            }

            //invalid student
            if (!$validStudent) {
                $nimList = "";
                for ($i = 0; $i < count($invalidNim); $i++) {
                    if ($i == 0) {
                        $nimList = $invalidNim[$i]->nim;
                    } else {
                        $nimList .= ", " . $invalidNim[$i]->nim;
                    }
                }
                $message = 'Student with the nim ' . $nimList . ' is not registered to this webinar. Please re-upload all of the certificates, and please upload certificate with the correct nim!';
                return $this->makeJSONResponse(['message' => $message], 400);
            } else {
                //valid student
                // return $this->makeJSONResponse(['message' => 'validation success'], 200);
                try {
                    $data = DB::transaction(function () use ($request) {
                        if ($request->hasFile('certificate')) {
                            $certificateAll = $request->file('certificate');
                            foreach (array_slice($certificateAll, 0, 10) as $certi) {
                                //ambil nama sertifikat dengan format nim nama
                                $name = $certi->getClientOriginalName();
                                //split
                                $nim = explode("_", $name);

                                $validationNim = Validator::make(["nim" => $nim[0]], [
                                    "nim" => "numeric|exists:pgsql2." . $this->tbStudent . ",nim"
                                ]);
                                if ($validationNim->fails()) {
                                    return $this->makeJSONResponse($validationNim->errors(), 404);
                                } else {
                                    //terus get student_id cari berdasarkan nim nya di tabel student
                                    // $studentId = DB::connection('pgsql2')
                                    //     ->table($this->tbStudent)
                                    //     ->where("nim", "=", $nim[0])
                                    //     ->select("id as student_id", "email", "name")
                                    //     ->get();
                                    $studentId = DB::connection('pgsql2')->table($this->tbUserPersonal, 'user')
                                        ->leftJoin($this->tbStudent . ' as student', 'user.id', '=', 'student.user_id')
                                        ->where("student.nim", "=", $nim[0])
                                        ->select('student.id as student_id', 'user.email', 'user.first_name', 'user.last_name', 'student.school_id', 'student.nim')
                                        ->get();
                                    //terus get participant_id nya dari tabel participant berdasarkan student_id
                                    $participantId = DB::table($this->tbParticipant)
                                        ->where("student_id", "=", $studentId[0]->student_id)
                                        ->select("id as participant_id", "webinar_id")
                                        ->get();

                                    $orderStatus = DB::table($this->tbOrder)
                                        ->where("participant_id", "=", $participantId[0]->participant_id)
                                        ->select("status")
                                        ->get();
                                    $webinar = DB::table($this->tbWebinar)
                                        ->where('id', '=', $request->webinar_id)
                                        ->select('*')
                                        ->get();


                                    if ($orderStatus[0]->status == "success") {
                                        $path = $certi->store('certificate_internal', 'public');
                                        $data =  array(
                                            'certificate' => $path,
                                            'webinar_id' => $participantId[0]->webinar_id,
                                            'participant_id' => $participantId[0]->participant_id,
                                            'file_name' => $name,
                                        );

                                        $notif = array(
                                            'student_id'     => $studentId[0]->student_id,
                                            'webinar_normal_id' => $participantId[0]->webinar_id,
                                            'message_id'    => "Selamat Anda telah mengikuti " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_start . " sertifikat anda telah kami kirimkan ke alamat email anda " . $studentId[0]->email,
                                            'message_en'    => "Congratulation you have attended " . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_start . " your certificae had been sent to your email" . $studentId[0]->email
                                        );

                                        try {
                                            CertificateJob::dispatch($webinar, $studentId, $data);
                                            DB::table($this->tbCertficate)->insert($data);
                                            DB::table($this->tbNotification)->insert($notif);
                                        } catch (Exception $e) {
                                            echo $e;
                                        }
                                    } else {
                                        $message = "cannot save, order status not sucess";
                                        $code = 400;
                                        return $this->makeJSONResponse(["message" => $message], $code);
                                    }
                                }
                            }
                            //response
                            $webinar = DB::table($this->tbWebinar)
                                ->where('id', '=', $request->webinar_id)
                                ->select('*')
                                ->get();

                            // $detail = DB::select("select * from " . $this->tbWebinar . " as web left join " . $this->tbParticipant . " as school on school.webinar_id = web.id where web.id = " . $request->webinar_id);

                            $participant = DB::table($this->tbParticipant, 'participant')
                                ->leftJoin($this->tbOrder . " as order", "participant.id", "=", "order.participant_id")
                                ->where("order.status", "=", "success")
                                ->select("student_id")
                                ->get();

                            $dataStudent = [];
                            $dataSchool = [];
                            for ($i = 0; $i < count($participant); $i++) {
                                $school = DB::connection('pgsql2')->table($this->tbStudent, "student")
                                    ->leftJoin($this->tbSch . " as school", "student.school_id", "=", "school.id")
                                    ->where('student.id', '=', $participant[$i]->student_id)
                                    ->select("school_id")
                                    ->get();
                                $dataSchool[$i] = $school[0];
                            }
                            for ($i = 0; $i < count($dataSchool); $i++) {
                                $temp = DB::connection('pgsql2')->table($this->tbSch)
                                    ->where('id', '=', $dataSchool[$i]->school_id)
                                    ->select('*')
                                    ->get();

                                $dataStudent[$i] = $temp[0];
                            }
                            $unique = array_values(array_unique($dataStudent, SORT_REGULAR));

                            $response = array(
                                "id"   => $webinar[0]->id,
                                "event_name" => $webinar[0]->event_name,
                                "event_date" => $webinar[0]->event_date,
                                "event_start" => $webinar[0]->event_start,
                                "event_end" => $webinar[0]->event_end,
                                "event_picture" => env("WEBINAR_URL") . $webinar[0]->event_picture,
                                "schools"    => $unique,
                                "event_link" => $webinar[0]->event_link,
                                "is_certificate" => true,
                                "certificate" => count($certificateAll),
                                // "array" => $arrayNim
                            );
                            $code = 200;
                            return $this->makeJSONResponse($response, $code);
                        }
                    });
                    if ($data) {
                        return $this->makeJSONResponse($data->original, 200);
                    } else {
                        return $this->makeJSONResponse(["message" => "transaction failed, student status must be success to get certificate!"], 400);
                    }
                } catch (Exception $e) {
                    echo $e;
                }
            }
        }
    }
}
