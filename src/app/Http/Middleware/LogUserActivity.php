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
        $data = [
            'people_id' => $user,
            'device' => 'mobile',
            'action' => $request->input('query'),
        ];
        $this->saveLogActivity($data);
        return $next($request);
    }
}
