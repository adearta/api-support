<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentChatBoxController;
use App\Http\Controllers\BroadcastChat\BroadcastController;
use App\Http\Controllers\Payment\WebinarPaymentController;
use App\Http\Controllers\StudentNormalWebinarParticipantController;
use App\Http\Controllers\WebinarNormalController;
use App\Http\Controllers\WebinarOrderController;

Route::group(['prefix' => 'messaging'], function () {
    Route::group(['prefix' => 'broadcast'], function () {
        Route::middleware('admin')->group(function () {
            Route::get('/detail', [BroadcastController::class, 'detail']);
        });
    });
    Route::group(['prefix' => 'channel'], function () {
        Route::post('/send-chat', [StudentChatBoxController::class, 'createChatStudent']);
        Route::get('/school', [StudentChatBoxController::class, 'listOfChat']);
        Route::delete('/delete-chat/{chat_id}', [StudentChatBoxController::class, 'deleteChat']);
        Route::get('/school/detail', [StudentChatBoxController::class, 'detailSchool']);
        Route::get('/detail', [StudentChatBoxController::class, 'detailChannel']);
    });
    // Route::get('/count', [StudentChatBoxController::class, 'countChat']);
    Route::get('/readed', [StudentChatBoxController::class, 'setReaded']);
});

Route::group(['prefix' => 'webinar-internal'], function () {
    Route::get('/detail/{webinar_id}', [WebinarNormalController::class, 'detailNormalWebinar']);
    Route::post('/register', [StudentNormalWebinarParticipantController::class, 'registerStudent']);
    Route::get('/', [WebinarNormalController::class, 'listwebinar']);
    Route::get('/detail-list/student/{webinar_id}', [WebinarNormalController::class, 'detailNormalWebinarWithStudent']);

    Route::group(['prefix' => 'order'], function () {
        Route::get('/detail/{webinar_id}', [WebinarOrderController::class, 'getOrderDetail']);
        Route::post('/charge', [WebinarPaymentController::class, 'charge']);
    });
});
