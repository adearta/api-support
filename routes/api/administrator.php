<?php

use App\Http\Controllers\BroadcastChat\BroadcastController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\WebinarAkbarController;
use App\Http\Controllers\SchoolParticipantAkbarController;
use App\Http\Controllers\NotificationWebinarController;
use App\Http\Controllers\StudentNormalWebinarParticipantController;
use App\Http\Controllers\WebinarNormalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\WebinarPaymentController;
use App\Http\Controllers\WebinarOrderController;
use App\Http\Controllers\SchoolChatBoxController;
use App\Http\Controllers\StudentChatBoxController;
use App\Jobs\CertificateAkbarJob;

Route::group(['prefix' => 'webinar-akbar'], function () {
    Route::middleware('admin')->group(function () {
        Route::post('/create', [WebinarAkbarController::class, 'addWebinar']);
        Route::delete('/delete/{webinar_id}', [WebinarAkbarController::class, 'destroyWebinar']);
        Route::post('/edit/{webinar_id}', [WebinarAkbarController::class, 'editWebinar']);
        Route::get('/', [WebinarAkbarController::class, 'listWebinar']);
        Route::get('/list-school', [SchoolParticipantAkbarController::class, 'listSchool']);
        Route::get('/webinar-by-school/{id}', [WebinarAkbarController::class, 'getWebinarBySchoolId']);
        Route::post('/update-status', [SchoolParticipantAkbarController::class, 'updateSchoolWebinar']);
        Route::get('/detail/{webinar_id}', [WebinarAkbarController::class, 'detailWebinar']);
        Route::get('/participant/{webinar_id}', [WebinarAkbarController::class, 'participantList']);
        Route::post('/detail/upload-certificate', [CertificateController::class, 'addCertificateAkbar']);
        Route::group(['prefix' => 'notification'], function () {
            Route::get('/', [NotificationWebinarController::class, 'getNotification']);
            Route::post('/read', [NotificationWebinarController::class, 'setNotificationReaded']);
        });
    });
});
Route::group(['prefix' => 'webinar-internal'], function () {
    Route::middleware('admin')->group(function () {
        Route::post('/create', [WebinarNormalController::class, 'addNormalWebinar']);
        Route::post('/edit/{webinar_id}', [WebinarNormalController::class, 'editWebinar']);
        Route::delete('/delete/{webinar_id}', [WebinarNormalController::class, 'destroyWebinar']);
        Route::get('/', [WebinarNormalController::class, 'listWebinar']);
        Route::get('/detail/{webinar_id}', [WebinarNormalController::class, 'detailNormalWebinar']);
        Route::get('/detail-list/student/{webinar_id}', [WebinarNormalController::class, 'detailNormalWebinarWithStudent']);
        Route::get('/order/detail', [WebinarOrderController::class, 'getDetailOrder']);
        Route::post('/register', [StudentNormalWebinarParticipantController::class, 'registerStudent']); //ok
        Route::post('/detail/upload-certificate', [CertificateController::class, 'addCertificate']);
        Route::group(['prefix' => 'notification'], function () {
            Route::get('/', [NotificationWebinarController::class, 'getNotification']);
            Route::post('/read', [NotificationWebinarController::class, 'setNotificationReaded']);
        });
        Route::group(['prefix' => 'payment'], function () {
            Route::get('/charge', [WebinarPaymentController::class, 'charge']);
        });
    });
});

// Route::group(['prefix' => 'student-chat'], function () {
//     Route::post('/inbox', [StudentChatBoxController::class, 'createChatStudent']);
//     Route::get('/school', [StudentChatBoxController::class, 'listOfChat']);
//     Route::delete('/delete-chat/{chat_id}', [StudentChatBoxController::class, 'deleteChat']);
//     Route::get('/school/detail', [StudentChatBoxController::class, 'detailSchool']);
// });
