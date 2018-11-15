<?php

namespace App\Http\Middleware;
use Symfony\Component\HttpFoundation\Response;
use JWTAuth;
use Closure;
use App\User;

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
    public function handle($request, Closure $next)
    {
        $request->attributes->add(['isLogged' => false]);
        if ($request->bearerToken() === null) {
            return $next($request);
        }
        try {
            JWTAuth::setToken($request->bearerToken()) ;
            //Get user id from the payload
            $payload = JWTAuth::parseToken()->getPayload();

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return $next($request);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return $next($request);
            }else{
            }
        }
        return response()->json([
            'response' => 'error',
            'message' => 'Cette action ne peut se faire que si vous n\'etes pas connecte'
        ],401);
    }
}
