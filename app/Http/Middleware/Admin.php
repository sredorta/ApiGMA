<?php

namespace App\Http\Middleware;
use JWTAuth;
use Closure;
use App;
use App\Account;
use App\User;
use Illuminate\Support\Facades\Config;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //Set language from HTTP header
        if ($request->header('Accept-Language') !== null) {
            $locale = substr($request->header('Accept-Language'),0,2);
            if (!in_array($locale, Config::get('constants.LANGUAGES'))) $locale = Config::get('app.fallback_locale');
            app::setLocale($locale);            
        }         
        try {
            if ($request->bearerToken() === null) {
                return response()->json([
                    'response' => 'error',
                    'message' => __('auth.login_required')
                ],401);
            }    
            JWTAuth::setToken($request->bearerToken()) ;
            //Get user id from the payload
            $payload = JWTAuth::parseToken()->getPayload();
            $user = $payload->get('user_id');
            $account = $payload->get('account_id');

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['error'=>__('auth.invalid')]);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['error'=>__('auth.expired')]);
            }else{
                return response()->json(['error'=>__('auth.error')]);
            }
        }
        //Get the language from the user
        $user = User::find($user);
        if ($user) {
             app::setLocale($user->language);
        }         
        if (Account::find($account)->access !== Config::get('constants.ACCESS_ADMIN')) {
            return response()->json([
                'response' => 'error',
                'message' => __('auth.admin_required')
            ],401);
        }

        //We should here send parameter profile_id to the route so that we don't need to find again
        $request->attributes->add(['isLogged' => true, 'myUser' => $user, 'myAccount' => $account]);

        return $next($request);
    }
}