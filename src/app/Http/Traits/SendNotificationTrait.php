<?php

namespace App\Http\Traits;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\FcmNotificationActionTypeEnum;
use App\Models\DocumentSignatureSent;
use App\Models\InboxReceiver;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

trait SendNotificationTrait
{
    public function setupInboxReceiverNotification($request)
    {
        $inboxReceiver = InboxReceiver::whereIn('To_Id', $request['data']['peopleIds'])
                                    ->where('NId', $request['data']['inboxId'])
                                    ->where('GIR_Id', $request['data']['groupId'])
                                    ->with('personalAccessTokens')
                                    ->get();
        if (!$inboxReceiver) {
            return response()->json(['message' => 'Data empty'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        foreach ($inboxReceiver as $message) {
            $token = $message->personalAccessTokens->pluck('fcm_token');
            $messageAttribute = $this->setNotificationAttribute($token, $request['notification'], $message->id, FcmNotificationActionTypeEnum::INBOX_DETAIL());
            $this->sendNotification($messageAttribute);
        }

        return true;
    }

    public function setupDocumentSignatureSentNotification($request)
    {
        $documentSignatureSent = DocumentSignatureSent::whereIn('id', $request['data']['documentSignatureSentId']);
        if ($request->target == DocumentSignatureSentNotificationTypeEnum::SENDER()) {
            $documentSignatureSent->with('senderPersonsalAccessTokens');
        }

        if ($request->target == DocumentSignatureSentNotificationTypeEnum::RECEIVER()) {
            $documentSignatureSent->with('receiverPersonsalAccessTokens');
        }

        $documentSignatureSent->get();

        if (!$documentSignatureSent) {
            return response()->json(['message' => 'Data empty'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        foreach ($documentSignatureSent as $message) {
            if ($request->target == DocumentSignatureSentNotificationTypeEnum::SENDER()) {
                $token = $message->senderPersonsalAccessTokens->pluck('fcm_token');
            }

            if ($request->target == DocumentSignatureSentNotificationTypeEnum::RECEIVER()) {
                $token = $message->receiverPersonsalAccessTokens->pluck('fcm_token');
            }

            $messageAttribute = $this->setNotificationAttribute($token, $request['notification'], $message->id, FcmNotificationActionTypeEnum::DOC_SIGNATURE_DETAIL());
            $this->sendNotification($messageAttribute);
        }

        return true;
    }

    /**
     * setNotificationAttribute
     *
     * @param  array $token
     * @param  array $notification
     * @param  string $id
     * @param  enum $action
     * @return array
     */
    public function setNotificationAttribute($token, $notification, $id, $action)
    {
        $messageAttribute = [
            'registration_ids' => $token,
            'notification' => $notification,
            'data' => [
                'id' => $id,
                'action' => $action
            ]
        ];

        return $messageAttribute;
    }

    /**
     * sendNotification
     *
     * @param  mixed $request
     * @return void
     */
    public function sendNotification($request)
    {
        $SERVER_API_KEY = config('fcm.server_key');

        $data = [
            'registration_ids' => $request['registration_ids'],
            'notification' => $request['notification'],
            'data' => $request['data']
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $SERVER_API_KEY,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $data);

        return json_decode($response);
    }
}
