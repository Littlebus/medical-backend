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

Route::post('/user/login', 'Auth\LoginController@login');
Route::post('/user/logout', 'Auth\LoginController@logout')->name('logout');
Route::post('/user/register', 'Auth\RegisterController@register')->name('register');
Route::middleware('auth')->get('/user', 'Auth\LoginController@getSelf');

Route::get('/type', 'MetaController@getAll');
Route::middleware('auth')->post('/type/{id}', 'UploadController')->name('upload');
Route::middleware('auth')->get('/type/{id}', 'RetrieveController@getList')->name('retrieveList');
Route::middleware('auth')->get('/type/{type_id}/{id}', 'RetrieveController@detail')->name('retrieveDetail');

Route::get('/test', 'MetaController@test')->name('test');
