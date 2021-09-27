<?php

namespace App\Http\Controllers;

use App\Models\CareerSupportModelsNormalStudentParticipants;
use App\Models\CareerSupportModelsWebinarBiasa;
use Illuminate\Http\Request;
use App\Traits\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CareerSupportModelsPercentage;
use App\Models\CareerSupportModelsOrdersWebinar;
use App\Models\NotificationWebinarModel;
use App\Models\StudentModel;
use App\Models\Association;
use App\Models\CandidatePortofolio;
use App\Models\UserAchievement;
use App\Models\UserLanguage;
use App\Models\UserPersonality;
use App\Models\UserSkill;
use App\Models\UserWorkExperience;
use App\Models\UserEducationModel;
use App\Models\UserPersonal;

class StudentNormalWebinarParticipantController extends Controller
{
    use ResponseHelper;

    //
    private $tbWebinar;
    private $tbParticipant;
    private $tbNotif;
    private $tbPercentage;
    private $tbOrder;
    // career_support_models_personalinfo
    private $tbStudent;
    // career_support_models_association
    private $tbAssoc;
    // career_support_models_user
    private $tbUserPersonal;
    // career_support_models_userskill
    private $tbSkill;
    // career_support_models_userpersonality
    private $tbPersonality;
    //  career_support_models_userlanguage
    private $tbLanguage;
    // career_support_models_usereducation
    private $tbUserEdu;
    // career_support_models_achievement
    private $tbAchievement;
    // career_support_models_workexperience
    private $tbWorkExp;
    // career_support_models_candidateportfolio
    private $tbPortofolio;

    public function __construct()
    {
        $this->tbWebinar = CareerSupportModelsWebinarBiasa::tableName();
        $this->tbParticipant = CareerSupportModelsNormalStudentParticipants::tableName();
        $this->tbNotif = NotificationWebinarModel::tableName();
        $this->tbPercentage = CareerSupportModelsPercentage::tableName();
        $this->tbOrder = CareerSupportModelsOrdersWebinar::tableName();

        $this->tbStudent = StudentModel::tableName();
        $this->tbAssoc = Association::tableName();
        $this->tbUserPersonal = UserPersonal::tableName();
        $this->tbSkill = UserSkill::tableName();
        $this->tbPersonality = UserPersonality::tableName();
        $this->tbLanguage = UserLanguage::tableName();
        $this->tbUserEdu = UserEducationModel::tableName();
        $this->tbAchievement = UserAchievement::tableName();
        $this->tbWorkExp = UserWorkExperience::tableName();
        $this->tbPortofolio = CandidatePortofolio::tableName();

        // add

    }

    public function registerStudent(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'webinar_id' => 'required|numeric|exists:' . $this->tbWebinar . ',id',
            'student_id' => 'required|numeric|exists:pgsql2.' . $this->tbStudent . ',id',
        ]);
        if ($validation->fails()) {
            return $this->makeJSONResponse(['message' => $validation->errors()->first()], 400);
        } else {
            $result = DB::transaction(function () use ($request) {
                $message = " ";
                $code = " ";
                //diganti
                // $profilePercentage = DB::connection('pgsql2')->select('select percent.percent, std.id as student_id from ' . $this->tbPercentage . " as percent left join " . $this->tbStudent . " as std on percent.user_id = std.user_id where percent.id = " . $request->student_id);
                $profilePercentage = DB::connection('pgsql2')
                    ->select("select ucp.profile_completeness as completeness from (select cu.first_name,cu.last_name,cu.date_joined,cu.is_active,cu.email,cu.id as user_id,cdp.id as personal_id,cdp.school_id,to_json(cu) as user,to_json(cdp) as personal,(case when ((case when (length(cdp.avatar) > 3) then 10 else 0 end)+(case when (cu.first_name is not null and cdp.gender is not null and cdp.date_of_birth is not null and cdp.phone is not null and cu.email is not null) then 10 else 0 end)+(case when (select (count(candidateskill) > 0) as res from " . $this->tbSkill . " as candidateskill where candidateskill.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidatepersonality) > 0) as res from " . $this->tbPersonality . " as candidatepersonality where candidatepersonality.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidatelanguage) > 0) as res from " . $this->tbLanguage . " as candidatelanguage where candidatelanguage.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateeducation) > 0) as res from " . $this->tbUserEdu . " as candidateeducation where candidateeducation.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateachievement) > 0) as res from " . $this->tbAchievement . " as candidateachievement where candidateachievement.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateworkexperience) > 0) as res from " . $this->tbWorkExp . " as candidateworkexperience where candidateworkexperience.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateportofolio) > 0) as res from " . $this->tbPortofolio . " as candidateportofolio where candidateportofolio.candidate_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateorganization) > 0) as res from " . $this->tbAssoc . " as candidateorganization where candidateorganization.user_id = cu.id) then 10 else 0 end))=0 then 10 else ((case when (length(cdp.avatar) > 3) then 10 else 0 end)+(case when (cu.first_name is not null and cdp.gender is not null and cdp.date_of_birth is not null and cdp.phone is not null and cu.email is not null) then 10 else 0 end)+(case when (select (count(candidateskill) > 0) as res from " . $this->tbSkill . " as candidateskill where candidateskill.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidatepersonality) > 0) as res from " . $this->tbPersonality . " as candidatepersonality where candidatepersonality.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidatelanguage) > 0) as res from " . $this->tbLanguage . " as candidatelanguage where candidatelanguage.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateeducation) > 0) as res from " . $this->tbUserEdu . " as candidateeducation where candidateeducation.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateachievement) > 0) as res from " . $this->tbAchievement . " as candidateachievement where candidateachievement.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateworkexperience) > 0) as res from " . $this->tbWorkExp . " as candidateworkexperience where candidateworkexperience.user_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateportofolio) > 0) as res from " . $this->tbPortofolio . " as candidateportofolio where candidateportofolio.candidate_id = cu.id) then 10 else 0 end)+(case when (select (count(candidateorganization) > 0) as res from " . $this->tbAssoc . " as candidateorganization where candidateorganization.user_id = cu.id) then 10 else 0 end))end) as profile_completeness from " . $this->tbStudent . " as cdp left join " . $this->tbUserPersonal . " as cu on cdp.user_id = cu.id where cdp.id = " . $request->student_id . " and cu.is_candidate=true) as ucp group by ucp.profile_completeness");

                $registered = DB::select("select count(pesan.webinar_id) as registered from " . $this->tbOrder . " as pesan left join " . $this->tbWebinar . " as web on web.id = pesan.webinar_id where pesan.status != 'order' and pesan.status != 'expire'");
                $statusRegis = DB::table($this->tbParticipant)->where('student_id', '=', $request->student_id)->where('webinar_id', '=', $request->webinar_id)->get();
                $webinar_id = null;
                //     // check if the student have the percent of profile or percent of profile is under 60
                if ($profilePercentage[0]->completeness < 60) {
                    $message = "please complete your profile, minimum 60% profile required";
                    $code = 400;
                } elseif ($statusRegis) {
                    $message = "you already register to this webinar!";
                    $code = 400;
                } else {
                    if ($registered[0]->registered < 500) {
                        //register
                        //$result = DB::transaction(function () use ($request, $profilePercentage, $message, $code) {
                        $webinar = DB::table($this->tbWebinar)
                            ->where('id', '=', $request->webinar_id)
                            ->get();

                        //get the student data from list of participant webinar
                        $pariticipant = DB::table($this->tbParticipant, 'participant')
                            ->leftJoin($this->tbWebinar . " as web", 'web.id', '=', 'participant.webinar_id')
                            ->where('participant.student_id', '=', $request->student_id)
                            ->where('web.event_date', '=', $webinar[0]->event_date)
                            ->where('web.event_start', '<=', $webinar[0]->event_start)
                            ->where('web.event_end', '>=', $webinar[0]->event_start)
                            ->where('web.event_start', '<=', $webinar[0]->event_end)
                            ->where('web.event_end', '>=', $webinar[0]->event_end)
                            ->get();
                        //check if the student has been registered on other webinar with the same time before            
                        if (count($pariticipant) > 0) {
                            $message = "Cannot register to this event because this student has been registered on other webinar with the same time before";
                            $code = 400;
                        } else {
                            //inser to participant table
                            $participant = DB::table($this->tbParticipant)->insertGetId(array(
                                'webinar_id' => $request->webinar_id,
                                'student_id' => $request->student_id,
                            ));

                            //simpan ke order
                            DB::table($this->tbOrder)->insert(array(
                                'participant_id' => $participant,
                                'webinar_id' => $request->webinar_id,
                                //
                            ));
                            //simpan ke notif
                            DB::table($this->tbNotif)->insert(array(
                                'student_id' => $request->student_id,
                                'webinar_normal_id' => $request->webinar_id,
                                'message_id'    => "Anda telah mendaftar untuk mengikuti Webinar dengan judul " . $webinar[0]->event_name . " pada tanggal " . $webinar[0]->event_date . " dan pada jam " . $webinar[0]->event_start,
                                'message_en'    => "You have been register to join a webinar with a title" . $webinar[0]->event_name . " on " . $webinar[0]->event_date . " and at " . $webinar[0]->event_start
                            ));
                            $orderId = DB::table($this->tbOrder)->where('participant_id', '=', $participant)->select('id', 'webinar_id')->get();
                            $message = $orderId[0]->id;
                            $webinar_id = $orderId[0]->webinar_id;
                            $code = 201;
                        }


                        // //});
                        // if ($result) {
                        //     $message = $result['message'];
                        //     $code = $result['code'];
                        // } else {
                        //     $message = "failed";
                        //     $code = 400;
                        // }
                    } else {
                        $message = $registered;
                        $code = 400;
                    }
                }

                return array(
                    'status'    => true,
                    'message'   => $message,
                    'webinar_id' => $webinar_id,
                    'code'      => $code
                );
            });

            if ($result && $result['code'] == 201) {
                return $this->makeJSONResponse([
                    "order_id" => $result['message'],
                    "webinar_id" => $result['webinar_id']
                ], $result['code']);
            } elseif ($result && $result['code'] == 400) {
                return $this->makeJSONResponse([
                    'message' => $result['message']
                ], $result['code']);
            } else {
                return $this->makeJSONResponse(["message" => "transaction failed!"], 400);
            }
        }
    }
}
