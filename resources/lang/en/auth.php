<?php


if(env('APP_ENV') === 'testing')
    return['language_test' => 'english'];
return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'expired'  => 'Your session has expired',
    'invalid'  => 'Session not valid',
    'error'    => 'Error in the session',
    'login_required' => 'You need to be logged in to perform this operation',
    'admin_required' => 'This operation requires administrator rights',
    'already_loggedin' => 'This operation requires not to be logged in',
    'user_already_exists' => 'Email or phone already registered in the system',
    'user_not_found' => 'Requested user could not be found',
    'signup_success' => "Success signup. Please validate your email and login",
    'login_validate' => "You need to validate your email to get access",
    'account_missing' => "Account not found",
    'token_error' => "The authentification token could not be created",
    'email_failed' => "This email does not match our records",
    'reset_success' => "A new password has been sent to you by email",
    'update_success' => "Your changes have been applied correctly",
    'update_phone_found' => "This phone is already registered in our records",
    'update_password' => "Invalid password",
    'update_email' => "This email already exists in our records",
    'language_unsupported' => "This language is not supported",
    'account_not_available' => "This account is not supported",
    'account_already'   => 'The user already have this account!',
    'account_not_found' => 'The user does not have this account!',
    'account_toggle' => 'Account toggling failed, please verify accounts of user',
    'test' => 'test in english. Well done :param'

];
