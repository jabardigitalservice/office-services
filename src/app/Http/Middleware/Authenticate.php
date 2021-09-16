<?php

namespace App\Http\Middleware;

use App\Exceptions\InvalidTokenException;
use App\Models\People;
use Closure;
use Firebase\JWT\JWT;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if ($request->headers->get('Authorization')) {
            $decoded = $this->decodeJwt($request);

            $people = People::where([
                'PeopleId' => $decoded->identifier,
            ])->first();

            $request->request->add(['people' => $people]);
        }

        return $next($request);
    }

    protected function getJwt(Request $request)
    {
        $authHeader = $request->headers->get('Authorization');
        return explode(" ", $authHeader)[1];
    }

    protected function decodeJwt(Request $request)
    {
        try {
            $decoded = JWT::decode($this->getJwt($request), config('jwt.secret'), array(config('jwt.algo')));
        } catch (\Exception $e) {
            throw new InvalidTokenException('Server Error');
        }
        return $decoded;
    }
}
