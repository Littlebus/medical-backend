<?php

use Illuminate\Http\Request;

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

Route::middleware('auth')->post('/ecg/predict', 'AppController@ecgPredict');
Route::middleware('auth')->get('/ecg/predict', 'AppController@getEcgPredict');
Route::middleware('auth')->get('/ecg/history', 'AppController@getEcgHistory');


Route::middleware('auth')->get('/messages', 'MessageController@getMessageList');
Route::middleware('auth')->post('/messages', 'MessageController@send');
Route::middleware('auth')->get('/contacts', 'MessageController@getChatList');

Route::middleware('auth')->get('/devices', 'DeviceController@getList');
Route::middleware('auth')->post('/devices', 'DeviceController@insert');

Route::middleware('auth')->post('/pe/predict', 'AppController@pePredict');
Route::middleware('auth')->get('/pe/predict', 'AppController@getPePredict');