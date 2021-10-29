<?php

namespace App\Http\Controllers\V1;

use App\Enums\FcmNotificationActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Traits\SendNotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
                'title' => $request->title,
                'body' => $request->body,
            ]
        ];

        switch ($request->action) {
            case FcmNotificationActionTypeEnum::INBOX_DETAIL():
                $messageAttribute['data'] = [
                    'inboxId' => $request->inboxId,
                    'groupId' => $request->groupId,
                    'peopleIds' => $request->peopleIds
                ];

                $doNotification = $this->setupInboxReceiverNotification($messageAttribute);
                break;

            case FcmNotificationActionTypeEnum::DOC_SIGNATURE_DETAIL():
                $messageAttribute['data'] = [
                    'documentSignatureSentId' => $request['documentSignatureSentId'],
                    'target' => $request['target']
                ];

                $doNotification = $this->setupDocumentSignatureSentNotification($messageAttribute);
                break;

            default:
                return response()->json(['message' => 'Action undefined'], Response::HTTP_INTERNAL_SERVER_ERROR);
                break;
        }

        return $doNotification;
    }
}
