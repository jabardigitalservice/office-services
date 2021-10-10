<?php

namespace App\Http\Traits;

use App\Enums\FcmNotificationActionTypeEnum;
use App\Models\InboxReceiver;
use Illuminate\Http\Response;

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
            $messageAttribute = [
                'registration_ids' => $message->personalAccessTokens->pluck('fcm_token'),
                'notification' => $request['notification'],
                'data' => [
                    'id' => $message->id,
                    'action' => FcmNotificationActionTypeEnum::INBOX_DETAIL()
                ]
            ];

            $this->sendNotification($messageAttribute);
        }

        return true;
    }

    public function sendNotification($request)
    {
        $SERVER_API_KEY = config('fcm.server_key');

        $data = [
            'registration_ids' => $request['registration_ids'],
            'notification' => $request['notification'],
            'data' => $request['data']
        ];

        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);

        return json_decode($response);

    }
}
