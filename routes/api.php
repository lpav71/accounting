<?php

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


Route::post('/orders/create', 'OrderController@ajaxStore');
Route::post('/phones/event', 'PhoneController@event');
Route::get('/products/{manufacturer}/xml', 'ParserController@xmlProducts');
Route::get('/product-picture-get/{productPicture}', 'ProductPictureController@getPicture')->name('product-pictures.api.show');
Route::post(config('telegram.bots.accounting_bot.token'),'TelegramWebhookController@webhook');
Route::get('/get-certificate-balance', 'CertificateController@getBalanceApi');
Route::post(config('telegram.bots.urgentAl_bot.token'), 'UrgentAlCallController@webhook');
