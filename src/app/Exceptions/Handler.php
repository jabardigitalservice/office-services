<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $exception)
    {
        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            if (auth()->check()) {
                $this->setUserContext(auth()->user());
            }

            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    private function setUserContext($user): void
    {
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($user) {
            $scope->setUser([
                'people_id' => $user->PeopleId,
                'people_name' => $user->PeopleName,
                'people_position' => $user->PeoplePosition,
                'people_username' => $user->PeopleUsername,
                'people_email' => $user->Email,
                'people_primary_role_id' => $user->PrimaryRoleId,
            ]);
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($messageError = $this->errorException($e)) {
            return $messageError;
        } else {
            $request->headers->set('Accept', 'application/json');
            return parent::render($request, $e);
        }
    }

    protected function errorException(Throwable $e)
    {
        $error = null;
        if ($e instanceof AuthenticationException) {
            $error = $this->errorResponse('Unauthenticated', 401);
        } elseif ($e instanceof ModelNotFoundException) {
            $error = $this->errorResponse('Object Not Found', 404);
        } elseif ($e instanceof NotFoundHttpException) {
            $error = $this->errorResponse('Url Not Found', 404);
        } elseif ($e instanceof HttpException) {
            $error = $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } elseif ($e instanceof AuthorizationException) {
            $error = $this->errorResponse($e->getMessage(), 403);
        }
        return $error;
    }
    /**
     * errorResponse
     *
     * @param  mixed $message
     * @param  mixed $code
     * @return void
     */
    protected function errorResponse($message, $code)
    {
        return response()->json([
            'error' => $message,
            'code' => $code,
        ], $code);
    }
}
