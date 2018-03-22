<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// 微信方面的路由
Route::namespace("Wechat")->group(function(){
    Route::any('/wechat','WechatController@index');
    Route::any('/jssdkconfig','WechatController@getJSSDKConfig');
});
