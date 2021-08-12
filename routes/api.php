<?php

use App\Http\Controllers\AssetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Payment\WebinarPaymentController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CandidateChannelListController;
use App\Http\Controllers\SchoolParticipantAkbarController;
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
Route::middleware('auth:api')->prefix('school')->group(base_path('routes/api/school.php'));
Route::middleware('auth:api')->prefix('candidate')->group(base_path('routes/api/candidate.php'));

Route::group(['prefix' => 'administrator'], function () {
	Route::post('/register', [AuthController::class, 'register']);
	Route::post('/login', [AuthController::class, 'login']);
	Route::post('/payment/status', [WebinarPaymentController::class, 'updateStatus']);
	Route::get('/img/{folder}/{img}', [AssetController::class, 'img']);
});

Route::get('/testing', [CertificateController::class, 'zipTest']);
Route::get('/candidatelistchannel', [CandidateChannelListController::class, 'listChannel']);
Route::get('/count', [CandidateChannelListController::class, 'countChat']);
