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

Route::namespace('Api')->group(function(){
    Route::any('img', 'ShareController@getImg');
    Route::any("info", "ShareController@info");
    Route::any('qrcode','ShareController@getQrcode');
    Route::any('douban','ShareController@getDouban');
    Route::any('message','ShareController@getMessage');
});


