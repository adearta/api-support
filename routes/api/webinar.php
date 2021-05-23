<?php

use App\Http\Controllers\WebinarAkbarController;
use App\Http\Controllers\SchoolParticipantAkbarController;
use App\Http\Controllers\StudentParticipantAkbarController;
use App\Http\Controllers\NotificationWebinarController;

use Illuminate\Support\Facades\Route;

Route::middleware('admin')->group(function () {
    Route::post('/create', [WebinarAkbarController::class, 'addWebinar']); // 1
});

Route::get('/list/{id}', [WebinarAkbarController::class, 'getWebinarBySchoolId']); // 3
Route::get('/detail/{id}', [WebinarAkbarController::class, 'detailWebinar']); //7,5
Route::group(['prefix' => 'school'], function () {
    Route::post('/add', [WebinarAkbarController::class, 'addSchoolParticipants'])->middleware('admin'); // 5
    Route::post('/update-status', [SchoolParticipantAkbarController::class, 'updateSchoolWebinar']); // 6
});

Route::group(['prefix' => 'notification'], function () {
    Route::get('/', [NotificationWebinarController::class, 'getNotification']); //2
    Route::post('/read', [NotificationWebinarController::class, 'setNotificationReaded']); //extra
});

////////////////////////////////////////////////////////////////////////

Route::post('/email', [WebinarAkbarController::class, 'sendMail'])->name('send-mail');
Route::delete('/delete/{id}', [WebinarAkbarController::class, 'destroy']); //ok tapi gabisa di jalankan karena dia direferences oleh tabel lain jadi arus hapus tabel lain dulu

//school participants
Route::put('/add-student-manual/{id}/{status}', [SchoolParticipantAkbarController::class, 'updateSchoolWebinar']);
Route::get('/dataSchool', [SchoolParticipantAkbarController::class, 'getSchoolData']); //ok
Route::get('/dataSchoolParticipants', [SchoolParticipantAkbarController::class, 'getSchoolParticipants']); //ok

//student participants
Route::get('/student-batch/{batch}', [StudentParticipantAkbarController::class, 'getStudentYearList']); //ok
Route::post('/add-student-manual', [StudentParticipantAkbarController::class, 'addStudentManual']); //ok
//student
Route::get('/student', [StudentParticipantAkbarController::class, 'getStudent']); //ok
Route::get('/count/{id}', [StudentParticipantAkbarController::class, 'getTotalParticipants']);

Route::get('/studentparticipants', [StudentParticipantAkbarController::class, 'addStudentParticipants']);
