<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return array|\Illuminate\Http\Response
     */
    public function __invoke()
    {
        return [
            'app' => config('app.name'),
            'server' => gethostname(),
        ];
    }
}
