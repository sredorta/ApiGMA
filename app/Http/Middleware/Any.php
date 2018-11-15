<?php

namespace App\Http\Middleware;
use JWTAuth;
use Closure;
use App\User;

class Any
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
        try {
            if ($request->bearerToken() === null) {
                $request->attributes->add(['isLogged' => false, 'myUser' => null, 'myAccount'=>null]);
                return $next($request);
            }    
            JWTAuth::setToken($request->bearerToken()) ;
            //Get user id from the payload
            $payload = JWTAuth::parseToken()->getPayload();
            $user = $payload->get('user_id');
            $account = $payload->get('account_id');

        } catch (Exception $e) {
            $request->attributes->add(['isLogged' => false, 'myUser' => null, 'myAccount' =>null]);
            return $next($request);
        }
        //We should here send user_id and account_id
        $request->attributes->add(['isLogged' => true, 'myUser' => $user, 'myAccount'=> $account]);
        return $next($request);
    }
}