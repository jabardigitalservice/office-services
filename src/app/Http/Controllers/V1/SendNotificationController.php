<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\SendNotificationTrait;
use Illuminate\Http\Request;

class SendNotificationController extends Controller
{
    use SendNotificationTrait;

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $sendNotification = $this->sendNotification($request);

        return $sendNotification;
    }
}
