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
   phpinfo();
});
Route::any('info', function () {
    phpinfo();
});
Route::get('user/index','UserController@index');
Route::post('weixin','WeixinController@wxEvent'); //接收事件推送
Route::get('weixin2','WeixinController@weixin2'); // 获取access_token
