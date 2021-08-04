<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentChatBoxController;
use App\Http\Controllers\BroadcastChat\BroadcastController;

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
