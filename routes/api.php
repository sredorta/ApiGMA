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

/*
Route::get('test', 'UserController@test');

Route::get('users/list', 'UserController@index');


Route::get('auth/test', 'AccountController@test');
Route::get('image/test', 'AttachmentController@imageTest');
*/

//Registered or not
Route::group(['middleware' => 'any'], function ($router) {
    Route::get('auth/user', 'AccountController@getAuthUser');
});

//Only if we are not loggedIn
Route::group(['middleware' => 'unregistered'], function ($router) {
    Route::post('auth/login', 'AccountController@login');
    Route::post('auth/signup', 'AccountController@signup'); 
    Route::get('auth/emailvalidate', 'AccountController@emailValidate');   //Return user from token
    Route::post('auth/resetpassword', 'AccountController@resetPassword');   //Resets password
});

Route::group(['middleware' => 'registered'], function ($router) {
    Route::post('auth/logout', 'AccountController@logout'); 
    Route::post('auth/update', 'AccountController@update'); 

    //Document handling
    Route::post('user/document/add', 'AttachmentController@addDocument');
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
