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
    Route::get('auth/lang/any', 'AccountController@language');
});

//Only if we are not loggedIn
Route::group(['middleware' => 'unregistered'], function ($router) {
    Route::post('auth/login', 'AccountController@login');
    Route::post('auth/signup', 'AccountController@signup'); 
    Route::get('auth/emailvalidate', 'AccountController@emailValidate');   
    Route::post('auth/resetpassword', 'AccountController@resetPassword');   //Resets password
    Route::get('auth/lang/unregistered', 'AccountController@language');
});
 
//Only if we are registerd with any access
Route::group(['middleware' => 'registered'], function ($router) {
    Route::get('auth/lang/registered', 'AccountController@language');
    Route::post('auth/logout', 'AccountController@logout');
    Route::post('auth/update', 'AccountController@update'); 
    Route::delete('auth/delete', 'AccountController@delete'); 
    //Notifications part
    Route::delete('notifications/delete', 'NotificationController@delete');
    Route::post('notifications/markread', 'NotificationController@markAsRead');
    Route::get('notifications', 'NotificationController@getAll');
    //  auth/delete
    //  all notifications, messages, imageables, attachables

    //Document handling
    Route::post('attachment/create', 'AttachmentController@create');
});

//Returns all data from all users including roles and accounts
Route::group(['middleware' => 'admin'], function ($router) {
    Route::get('auth/lang/admin', 'AccountController@language');
    Route::post('auth/account/create', 'AccountController@addAccount');         //Adds accounts to user
    Route::delete('auth/account/delete', 'AccountController@deleteAccount');    //Removes account from user
    Route::post('auth/account/toggle', 'AccountController@toggleAccount');      //toggles Pré-inscrit to Membre
    Route::get('roles' , 'RoleController@getRoles');              //Get all Roles
    Route::post('roles/attach' , 'RoleController@attachUser');    //Adds a role to a user
    Route::post('roles/detach' , 'RoleController@detachUser');    //Removes a role to a user
    Route::post('roles/create' , 'RoleController@create');        //Creates a new role
    Route::delete('roles/delete' , 'RoleController@delete');        //Deletes a role
    Route::delete('attachment/delete', 'AttachmentController@delete'); //Deletes a attachment by id
});





Route::get('test', 'UserController@test');
/*
Route::get('users/list', 'UserController@index');


Route::get('auth/test', 'AccountController@test');
Route::get('image/test', 'AttachmentController@imageTest');
*/