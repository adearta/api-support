<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Payment\WebinarPaymentController;
use App\Http\Controllers\StudentChatBoxController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//the routes of webinar
Route::middleware('auth:api')->prefix('administrator')->group(base_path('routes/api/administrator.php'));

Route::post('/administrator/register', [AuthController::class, 'register']);
Route::post('/administrator/login', [AuthController::class, 'login']);

//api for change the order status and triggered by midtrans
Route::post('/administator/payment/status', [WebinarPaymentController::class, 'updateStatus']);
Route::post('/student-chat/inbox', [StudentChatBoxController::class, 'createChatStudent']);
Route::get('/student-chat/school', [StudentChatBoxController::class, 'listOfChat']);
Route::delete('/student-chat/delete-chat/{chat_id}', [StudentChatBoxController::class, 'deleteChat']);
Route::get('/student_chat/school/detail', [StudentChatBoxController::class, 'detailSchool']);
