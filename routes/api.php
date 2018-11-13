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
Route::get('users/list', 'UserController@index');
Route::get('auth/test', 'AccountController@test');
Route::post('auth/login', 'AccountController@login');
Route::get('auth/user', 'AccountController@getAuthUser');
Route::post('auth/logout', 'AccountController@logout'); 
Route::post('auth/signup', 'AccountController@signup'); 

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
