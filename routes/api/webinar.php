<?php

use App\Http\Controllers\WebinarController;
use App\Http\Controllers\SchoolParticipantsController;
use App\Http\Controllers\StudentParticipantsController;
use App\Http\Controllers\NotificationController;

use Illuminate\Support\Facades\Route;

Route::post('/create', [WebinarController::class, 'addWebinar'])->middleware('admin'); //ok

Route::post('/email', [WebinarController::class, 'sendMail'])->name('send-mail');
Route::get('/webinarList', [WebinarController::class, 'getWebinar']); //ok
Route::delete('/delete/{id}', [WebinarController::class, 'destroy']); //ok tapi gabisa di jalankan karena dia direferences oleh tabel lain jadi arus hapus tabel lain dulu

//school participants
Route::put('/add-student-manual/{id}/{status}', [SchoolParticipantsController::class, 'updateSchoolWebinar']);
Route::get('/dataSchool', [SchoolParticipantsController::class, 'getSchoolData']); //ok
Route::get('/dataSchoolParticipants', [SchoolParticipantsController::class, 'getSchoolParticipants']); //ok

//student participants
Route::get('/student-batch/{batch}', [StudentParticipantsController::class, 'getStudentYearList']); //ok
Route::post('/add-student-manual', [StudentParticipantsController::class, 'addStudentManual']); //ok
//student
Route::get('/student', [StudentParticipantsController::class, 'getStudent']); //ok
//notification
Route::get('/school-notif/{id}', [NotificationController::class, 'getSchoolNotification']); //ok
Route::post('/student-notif/{id}', [NotificationController::class, 'getStudentNotification']);
