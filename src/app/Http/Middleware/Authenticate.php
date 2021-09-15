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
            list($decoded, $message) = $this->decodeJwt($request);

            if ($decoded) {
                $people = People::where([
                    'PeopleId' => $decoded->identifier,
                ])->first();
            } else {
                if ($message == 'Expired token') {
                    return $this->responseMessage($message);
                } else {
                    return $this->responseMessage('Invalid token');
                }
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
        return [$decoded, null];
    }

    protected function responseMessage($message)
    {
        return ['message' => $message];
    }
}
