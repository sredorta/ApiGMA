<?php

namespace App\Http\Middleware;
use JWTAuth;
use Closure;
use App;
use App\Account;
use App\User;
use Illuminate\Support\Facades\Config;

class Member
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
        if (Account::find($request->get('myAccount'))->access === Config::get('constants.ACCESS_DEFAULT')) {
            return response()->json([
                'response' => 'error',
                'message' => __('auth.member_required')
            ],401);
        }
        return $next($request);
    }
}