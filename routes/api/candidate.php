<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentChatBoxController;

Route::group(['prefix' => 'messaging'], function () {
    Route::group(['prefix' => 'personal'], function () {
        Route::post('/inbox', [StudentChatBoxController::class, 'createChatStudent']);
        Route::get('/school', [StudentChatBoxController::class, 'listOfChat']);
        Route::delete('/delete-chat/{chat_id}', [StudentChatBoxController::class, 'deleteChat']);
        Route::get('/school/detail', [StudentChatBoxController::class, 'detailSchool']);
    });
});
