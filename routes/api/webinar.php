<?php

use App\Http\Controllers\WebinarAkbarController;
use App\Http\Controllers\SchoolParticipantAkbarController;
use App\Http\Controllers\StudentParticipantAkbarController;
use App\Http\Controllers\NotificationWebinarController;
use Illuminate\Support\Facades\Route;


Route::middleware('admin')->group(function () {
    //create new webinar
    Route::post('/create', [WebinarAkbarController::class, 'addWebinar']); // 1 ok
});
//showing list of webinar which can be joined when invitation status of the webinar is not 3(acc) and (2)
Route::get('/list/{id}', [WebinarAkbarController::class, 'getWebinarBySchoolId']); // 3ok
//get list of webinar can joined by the school id
Route::get('/detail/{id}', [WebinarAkbarController::class, 'detailWebinar']); //7,5ok

Route::group(['prefix' => 'school'], function () {
    //add school participants by the admin when the school candidate not give student candidate until maximum date (3 days) 
    Route::post('/add', [WebinarAkbarController::class, 'addSchoolParticipants'])->middleware('admin'); // 5
    //updatiing status of the school participants
    /* the status of school
            1 -> created
            2 -> rejected
            3 -> accepted
            4 -> submit the data of student
            5.-> finished webinar
            */
    Route::post('/update-status', [SchoolParticipantAkbarController::class, 'updateSchoolWebinar']); // 6
});

Route::group(['prefix' => 'notification'], function () {
    //get the notification 
    Route::get('/', [NotificationWebinarController::class, 'getNotification']); //2
    //set notification status to read 
    Route::post('/read', [NotificationWebinarController::class, 'setNotificationReaded']); //extra
});
//super admin get the data from database (candidate name, and candidate school)
Route::get('/participant/{webinar_id}', [WebinarAkbarController::class, 'participantList']);
//get the detail of the webinar with schools that participate on it
Route::get('/detail/{webinar_id}', [WebinarAkbarController::class, 'detailWebinar']);

////////////////////////////////////////////////////////////////////////
