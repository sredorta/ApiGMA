<?php

namespace App\Http\Middleware;
use App;
use JWTAuth;
use Closure;
use App\User;

class Registered
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
            app::setLocale(substr($request->header('Accept-Language'),0,2));
        } 
        try {
            if ($request->bearerToken() === null) {
                return response()->json([
                    'response' => 'error',
                    'message' => 'not_loggedin'
                ],401);
            }    
            JWTAuth::setToken($request->bearerToken()) ;
            //Get user id from the payload
            $payload = JWTAuth::parseToken()->getPayload();
            $user = $payload->get('user_id');
            $account = $payload->get('account_id');

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['error'=>'token_invalid']);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['error'=>'token_expired']);
            }else{
                return response()->json(['error'=>'token_error']);
            }
        }

        //We add user_id and account_id
        $request->attributes->add(['isLogged' => true, 'myUser' => $user, 'myAccount'=>$account]);
        //Get the language from the user
        $user = User::find($user);
        if ($user) {
            app::setLocale($user->language);
        }         
        return $next($request);

    }
}
