<?php

use App\Http\Controllers\WebinarAkbarController;
use App\Http\Controllers\SchoolParticipantAkbarController;
use App\Http\Controllers\StudentParticipantAkbarController;
use App\Http\Controllers\NotificationWebinarController;
use Illuminate\Support\Facades\Route;


Route::middleware('admin')->group(function () {
    //super admin create new webinar
    //method post = > 
    /* the parameter used:
            1 -> zoom_link
            2 -> event_name
            3 -> event_date
            4 -> event_time
            5.-> event_picture
            6 -> school[]
            */
    Route::post('/create', [WebinarAkbarController::class, 'addWebinar']);
});
//get the detail of the webinar with schools that participate on it
//method get = > 
/* the parameter used:
            1 -> {webinar_id} => webinar_id
            */
Route::get('/detail/{webinar_id}', [WebinarAkbarController::class, 'detailWebinar'])->middleware('admin');
//method get = > 
/* the parameter used:
            1 -> {id} => school_id
            */
//showing list of webinar which can be joined when invitation status of the webinar is not 3(acc) and (2)
Route::get('/list/{id}', [WebinarAkbarController::class, 'getWebinarBySchoolId']);


Route::group(['prefix' => 'school'], function () {
    //add school participants by the admin when the school candidate not give student acandidate and not confirm until maximum date (3 days) 
    //method post = > 
    /* the parameter used:
            1 -> school_id
            2 -> webinar_id
            */
    Route::post('/add', [WebinarAkbarController::class, 'addSchoolParticipants'])->middleware('admin');
    //updatiing status of the school participants
    //method post = > 
    /* the parameter used:
            1 -> webinar_id
            2 -> school_id
            3 -> start_year ->use this param when school_id = x has set status to 4(submit the data of student) 
            4 -> status
            */
    /* the status of school
            1 -> created
            2 -> rejected
            3 -> accepted
            4 -> submit the data of student
            5.-> finished webinar
            */
    Route::post('/update-status', [SchoolParticipantAkbarController::class, 'updateSchoolWebinar']);
});


Route::group(['prefix' => 'notification'], function () {
    //get the notification
    //method get = >  
    /* the parameter used:
        on params 
            1 -> school_id
        on headers
            1 -> accept-language =>set to (id/en)
            */
    Route::get('/', [NotificationWebinarController::class, 'getNotification']);
    //set notification status to read 
    //method get = >  
    /* the parameter used:
        on params 
            1 -> notification_id
            */
    Route::post('/read', [NotificationWebinarController::class, 'setNotificationReaded']);
});
//super admin get the data from database (candidate name, and candidate school)
Route::get('/participant/{webinar_id}', [WebinarAkbarController::class, 'participantList']);


////////////////////////////////////////////////////////////////////////
