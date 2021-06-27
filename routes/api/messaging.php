<?php

use App\Http\Controllers\BroadcastChat\BroadcastController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchoolChatBoxController;
use App\Http\Controllers\StudentChatBoxController;

Route::group(['prefix' => 'broadcast'], function () {
    Route::middleware('admin')->group(function () {
        Route::post('/create', [BroadcastController::class, 'create']);
        Route::get('/', [BroadcastController::class, 'list']);
        Route::get('/detail', [BroadcastController::class, 'detail']);
        Route::delete('/delete/{broadcast_id}', [BroadcastController::class, 'delete']);
    });
});
Route::group(['prefix' => 'personal'], function () {
    Route::middleware('admin')->group(function () {
        Route::post('/channel/send-chat', [SchoolChatBoxController::class, 'createChat']); //
        Route::get('/channel/candidates', [SchoolChatBoxController::class, 'listChat']); //
        Route::delete('/delete-chat/{chat_id}', [SchoolChatBoxController::class, 'deleteChat']);
        Route::get('/channel', [SchoolChatBoxController::class, 'listRoom']); //
        Route::delete('/room/delete/{room_chat_id}', [SchoolChatBoxController::class, 'deleteRoom']);
        Route::get('/channel/{channel_id}', [SchoolChatBoxController::class, 'detailChat']); //new not tested
    });
});
