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
Route::any('user/index','UserController@index');
Route::any('/weixin','WeixinController@wxEvent'); //接收事件推送
Route::any('weixin2','WeixinController@weixin2'); // 获取access_token
Route::post('createmanu','WeixinController@createmanu'); // 菜单
Route::get('receive','WeixinController@receive'); // 菜单
Route::get('receiveEvent','WeixinController@receiveEvent'); // 菜单
Route::get('gettext','WeixinController@gettext'); // 文本处理
Route::get('shouquan','WeixinController@shouquan'); // 微信授权
Route::any('/code','WeixinController@codes'); // 获取code
Route::post('tianqi','WeixinController@tianqi'); // 天气
Route::get('time',function(){

    echo strtotime('Y-m-d');
    echo date('Y-m-d',time());
});

