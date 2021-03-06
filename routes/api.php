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
    Route::get('pages', 'PageController@getAll'); 
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
    Route::post('notifications/delete', 'NotificationController@delete');
    Route::post('notifications/markread', 'NotificationController@markAsRead');
    Route::get('notifications', 'NotificationController@getAll');
    //  auth/delete
    //  all notifications, messages, imageables, attachables

    //Document handling
    Route::post('attachment/create', 'AttachmentController@create');
});

//Only if we are registerd with any access
Route::group(['middleware' => ['registered', 'member']], function ($router) {
    Route::post('message/send', 'MessageController@send');
    Route::get('messages', 'MessageController@getAll');
    Route::post('message/markread', 'MessageController@markAsRead');
    Route::delete('message/delete', 'MessageController@delete');

    Route::get('groups' , 'GroupController@getAll');              //Get all Groups
    Route::get('roles' , 'RoleController@getRoles');              //Get all Roles

});




//Returns all data from all users including roles and accounts
Route::group(['middleware' => ['registered','admin']], function ($router) {
    Route::get('auth/lang/admin', 'AccountController@language');
    Route::post('auth/account/create', 'AccountController@addAccount');         //Adds accounts to user
    Route::delete('auth/account/delete', 'AccountController@deleteAccount');    //Removes account from user
    Route::post('auth/account/toggle', 'AccountController@toggleAccount');      //toggles Pré-inscrit to Membre
    Route::post('roles/attach' , 'RoleController@attachUser');    //Adds a role to a user
    Route::post('roles/detach' , 'RoleController@detachUser');    //Removes a role to a user
    Route::post('roles/create' , 'RoleController@create');        //Creates a new role
    Route::delete('roles/delete' , 'RoleController@delete');        //Deletes a role
    Route::post('groups/attach' , 'GroupController@attachUser');    //Adds a group to a user
    Route::post('groups/detach' , 'GroupController@detachUser');    //Removes a group to a user
    Route::post('groups/create' , 'GroupController@create');        //Creates a new group
    Route::delete('groups/delete' , 'GroupController@delete');        //Deletes a group    

    Route::delete('attachment/delete', 'AttachmentController@delete'); //Deletes a attachment by id

    //PAGE HANDLING

    Route::delete('pages/delete', 'PageController@delete');
    Route::post('pages/create', 'PageController@create');
    //Route::get('pages/attachments', 'PageController@getAttachments'); //MOVE ME TO ANY !!!!
    //Route::post('pages/attachments/create', 'PageController@addAttachment');

});





Route::get('test', 'UserController@test');
/*
Route::get('users/list', 'UserController@index');


Route::get('auth/test', 'AccountController@test');
Route::get('image/test', 'AttachmentController@imageTest');
*/