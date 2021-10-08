<?php

namespace App\Http\Controllers\V1;

use App\Enums\FcmNotificationActionTypeEnum;
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

        $messageAttribute = [
            'notification' => [
                'title' => $request->sender,
                'body' => $request->about . ' | ' . $request->typeName . ' | ' . $request->urgencyName,
            ],
            'data' => [
                'inboxId' => $request->inboxId,
                'groupId' => $request->groupId,
                'peopleIds' => $request->peopleIds
            ]
        ];

        $sendInboxReceiverNotification = $this->setupInboxReceiverNotification($messageAttribute);

        return $sendInboxReceiverNotification;
    }
}
