<?php

namespace App\Http\Middleware;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use JWTAuth;
use Closure;
use App\User;
use App;

//Checks that user is unregistered

class Unregistered
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //Set language from HTTP header
        if ($request->header('Accept-Language') !== null) {
            $locale = substr($request->header('Accept-Language'),0,2);
            if ($locale !== "en" && $locale !== "fr") $locale = 'fr';
            app::setLocale($locale);
        } 

        $request->attributes->add(['isLogged' => false]);
        if ($request->bearerToken() === null) {
            return $next($request);
        }
        try {
            JWTAuth::setToken($request->bearerToken()) ;
            //Get user id from the payload
            $payload = JWTAuth::setRequest($request)->parseToken()->getPayload();

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return $next($request);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return $next($request);
            } else {
                return $next($request);
            }
        }
        $user = User::find($user);
        if ($user) {
            app::setLocale($user->language);
        }   
        return response()->json([
            'response' => 'error',
            'message' => __('auth.already_loggedin')
        ],401);
    }
}
