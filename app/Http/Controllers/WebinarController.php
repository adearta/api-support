<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebinarCandidateModel;
use App\Models\NotificationCandidateModel;
use App\Models\SchoolParticipantsCandidateModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ResponseHelper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class WebinarController extends Controller
{
    //
    use ResponseHelper;

    public function getWebinar()
    {
        $webinar =  DB::select('select * from career_support_models_webinar');
        $auth = auth()->user();
        if ($auth) {
            return $this->makeJSONResponse($webinar, 200);
        } else {
            $message = "no data!";
            return $this->makeJSONResponse($message, 400);
        }
    }
    public function addWebinar(Request $request)
    {
        //validate data
        $this->validate($request, [
            'zoom_link' => 'required|url',
            'event_name' => 'required',
            'event_date' => 'required',
            'event_time' => 'required',
            'event_picture' => 'required|mimes:jpg,jpeg,png|max:2000',
        ]);
        //create data
        if ($file = $request->file('event_picture')) {
            $path = $file->store('public/files');

            $create = new WebinarCandidateModel();
            $create->zoom_link = $request->zoom_link;
            $create->event_name = $request->zoom_link;
            $create->event_date = $request->zoom_link;
            $create->event_time = $request->zoom_link;
            $create->event_picture = $path;
            //autentikasi token untuk menyimpan
            $auth = auth()->user()->admins()->save($create);

            if ($auth) {
                //create notif
                $notif = new NotificationCandidateModel();
                $notif->event_date = $request->zoom_link;
                $notif->event_time = $request->zoom_link;
                $notif->save();
                //create participants
                $participans = new SchoolParticipantsCandidateModel();
                $fillParticipants = $participans->create($request->all());
                $fillParticipants->save();
                //send mail
                $this->sendMail($request);

                $message_saved = "data saved!";
                return $this->makeJSONResponse($message_saved, 200);
            } else {
                $message_not_saved = "fail to create data!";
                return $this->makeJSONResponse($message_not_saved, 400);
            }
        }
    }
    public function updateWebinar(Request $request, $id)
    {
    }
    public function destroy($id)
    {
        $delete = WebinarCandidateModel::findOrfail($id);
        $auth = auth()->user();
        if ($auth) {
            $delete->delete();
            $message_ok = "deleted!";
            return $this->makeJSONResponse($message_ok, 200);
        } else {
            $message_err = "cant find data!";
            return $this->makeJSONResponse($message_err, 400);
        }
    }
    public function sendMail(Request $request)
    {
        // School::create($request->all());
        Mail::send('email', array(
            'zoom_link' => $request->get('zoom_link'),
            'name' => $request->get('name'),
            'date' => $request->get('date'),
            'time' => $request->get('time'),
            // 'email' => $request->get('email'),
        ), function ($message) use ($request) {
            //selecting all email from career_support_models_student_participants and save to array
            $broadcast = DB::select('select email from career_support_models_student_participants');
            $message->from('adeartakusumaps@gmail.com');
            //diganti broadcast ke semua email student participants.
            $message->to($broadcast, 'Hello Student')->subject($request->get('subject'));
        });
    }
}
