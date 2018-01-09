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

Route::get('/', 'WechatController@home');
Route::get('/jsapi', 'WechatController@jsapi');
Route::get('/micropay', 'WechatController@micropay');
Route::get('/native', 'WechatController@native');
Route::get('/orderQuery', 'WechatController@order');
Route::get('/refund', 'WechatController@refund');
Route::get('/refundquery', 'WechatController@refundquery');
Route::get('/download', 'WechatController@download');

Route::post('/postMicropay', 'WechatController@postMicropay');
Route::post('/orderQuery', 'WechatController@orderQuery');
