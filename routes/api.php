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




//Registered or not
Route::group(['middleware' => 'any'], function ($router) {
    Route::get('auth/user', 'AccountController@getAuthUser');
});

//Only if we are not loggedIn
Route::group(['middleware' => 'unregistered'], function ($router) {
    Route::post('auth/login', 'AccountController@login');
    Route::post('auth/signup', 'AccountController@signup'); 
    Route::get('auth/emailvalidate', 'AccountController@emailValidate');   
    Route::post('auth/resetpassword', 'AccountController@resetPassword');   //Resets password
});
 
//Only if we are registerd with any access
Route::group(['middleware' => 'registered'], function ($router) {
    Route::post('auth/logout', 'AccountController@logout');
    Route::post('auth/update', 'AccountController@update'); 
    Route::delete('auth/delete', 'AccountController@delete'); 
    //Notifications part
    Route::delete('notifications/delete', 'NotificationController@delete');
    Route::post('notifications/markread', 'NotificationController@markAsRead');
    Route::get('notifications/getAll', 'NotificationController@getAll');
    //  auth/delete
    //  all notifications, messages, imageables, attachables

    //Document handling
    Route::post('user/document/add', 'AttachmentController@addDocument');
});

//Returns all data from all users including roles and accounts
Route::group(['middleware' => 'admin'], function ($router) {
    Route::get('admin/roles' , 'RoleController@getRoles');              //Get all Roles
    Route::post('admin/roles/attach' , 'RoleController@attachUser');    //Adds a role to a user
    Route::post('admin/roles/detach' , 'RoleController@detachUser');    //Removes a role to a user
    Route::post('admin/roles/create' , 'RoleController@create');        //Creates a new role
    Route::post('admin/roles/delete' , 'RoleController@delete');        //Deletes a role
});





Route::get('test', 'UserController@test');
/*
Route::get('users/list', 'UserController@index');


Route::get('auth/test', 'AccountController@test');
Route::get('image/test', 'AttachmentController@imageTest');
*/