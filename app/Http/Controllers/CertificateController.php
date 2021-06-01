<?php

namespace App\Http\Controllers;

use App\Jobs\Certificate;
use App\Jobs\CertificateJob;
use App\Mail\SendCertificate;
use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\StudentModel;
use App\Models\CareerSupportModelsCertificate;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\CareerSupportModelsWebinarBiasa;
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
    use ResponseHelper;

    public function __construct()
    {
        $this->tbStudent = StudentModel::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbCertficate = CareerSupportModelsCertificate::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
    }
    //
    public function addCertificate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'certificate.*' => 'required|mimes:pdf|max:500',
            'webinar_id' => 'required|numeric',
            // 'participant_id'=>'required|numeric'
        ]);
        if ($validation->fails()) {
            $this->makeJSONResponse($validation->errors(), 400);
        } else {
            try {
                if ($request->hasFile('certificate')) {
                    $certificateAll = $request->file('certificate');
                    // ambil 10 sertifikat saja
                    foreach (array_slice($certificateAll, 0, 10) as $certi) {
                        //ambil nama sertifikat dengan format nim nama
                        //ambil nama
                        $name = $certi->getClientOriginalName();
                        //kemdian pisahin, ambil nim nya
                        //split
                        $nim = explode("_", $name);
                        //terus get student_id cari berdasarkan nim nya di tabel student
                        $studentId = DB::connection('pgsql2')
                            ->table($this->tbStudent)
                            ->where("nim", "=", $nim[0])
                            ->select("id as student_id", "email", "name")
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

                        $path = $certi->store('certificate', 'uploads');
                        // echo 'order status => ' . $orderStatus;
                        if ($orderStatus[0]->status == "success") {
                            // echo 'gass';
                            $data =  array(
                                'certificate' => $path,
                                'webinar_id' => $participantId[0]->webinar_id,
                                'participant_id' => $participantId[0]->participant_id,
                                'file_name' => $name,
                                //kirim email pake job
                            );
                            // $webinarMail = json_decode($webinar[0]);
                            // $studentMail = json_decode($studentId[0])
                            CertificateJob::dispatch($webinar, $studentId, $data);
                            DB::table($this->tbCertficate)->insert($data);
                        }
                    }
                    // echo $amount;
                    $message = "success save certificate ";
                    $code = 200;
                    return $this->makeJSONResponse(["message" => $message], $code);
                }
            } catch (Exception $e) {
                echo $e;
            }
        }
    }
}
