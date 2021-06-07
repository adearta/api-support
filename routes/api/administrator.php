<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\WebinarAkbarController;
use App\Http\Controllers\SchoolParticipantAkbarController;
use App\Http\Controllers\NotificationWebinarController;
use App\Http\Controllers\StudentNormalWebinarParticipantController;
use App\Http\Controllers\WebinarNormalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\WebinarPaymentController;
use App\Http\Controllers\WebinarOrderController;

Route::group(['prefix' => 'webinar-akbar'], function () {
    Route::middleware('admin')->group(function () {
        Route::post('/create', [WebinarAkbarController::class, 'addWebinar']);
        Route::delete('/delete/{webinar_id}', [WebinarAkbarController::class, 'destroyWebinar']);
        Route::patch('/edit', [WebinarAkbarController::class, 'editWebinar']);
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
        Route::patch('/edit/{webinar_id}', [WebinarNormalController::class, 'editWebinar']);
        Route::delete('/delete/{webinar_id}', [WebinarNormalController::class, 'destroyWebinar']);
        // Route::get('', [WebinarNormalController::class, 'listNormalWebinar']); //blm
        Route::get('/', [WebinarNormalController::class, 'listWebinar']);
        Route::get('/detail/{webinar_id}', [WebinarNormalController::class, 'detailNormalWebinar']);
        Route::get('/detail-list/student/{webinar_id}', [WebinarNormalController::class, 'detailNormalWebinarWithStudent']);
        Route::get('/order/detail', [WebinarOrderController::class, 'getDetailOrder']);
        Route::post('/register', [StudentNormalWebinarParticipantController::class, 'registerStudent']); //ok
        Route::post('/addcertificate', [CertificateController::class, 'addCertificate']);
        Route::group(['prefix' => 'notification'], function () {
            Route::get('/', [NotificationWebinarController::class, 'getNotification']);
            Route::post('/read', [NotificationWebinarController::class, 'setNotificationReaded']);
        });
        Route::group(['prefix' => 'payment'], function () {
            Route::get('/charge', [WebinarPaymentController::class, 'charge']);
        });
    });
});

Route::get('/reminder', [WebinarAkbarController::class, 'reminderStudent']);
