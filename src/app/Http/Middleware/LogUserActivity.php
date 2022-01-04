<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Traits\LogUserActivityTrait;
use Illuminate\Http\Request;

class LogUserActivity
{
    use LogUserActivityTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user()->PeopleId ?? null;
        $this->saveLogActivity($user, 'mobile', $request);
        return $next($request);
    }
}
