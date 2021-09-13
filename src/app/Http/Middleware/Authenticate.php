<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use App\Models\People;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if ($request->headers->get('Authorization')) {
            $decoded = $this->decodeJwt($request);

            if (!$decoded) {
                return $this->invalidToken();
            }
            $people = People::where([
                'PeopleId' => $decoded->identifier,
            ])->first();

            if (!$people) {
                return $this->invalidToken();
            }

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
            return [false, $e->getMessage()];
        }
        return $decoded;
    }

    protected function invalidToken()
    {
        return [
            'message' => 'Token Invalid'
        ];
    }
}
