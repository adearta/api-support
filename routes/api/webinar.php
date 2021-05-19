<?php

use App\Http\Controllers\WebinarController;
use Illuminate\Support\Facades\Route;

Route::post('/create', [WebinarController::class, 'addWebinar'])->middleware('admin');
