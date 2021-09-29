<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
                ]
            );
        });
    }
}
