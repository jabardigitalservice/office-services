<?php

namespace App\Http\Traits;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Response;

trait SendNotificationTrait
{
    public function sendNotification($request)
    {
        $SERVER_API_KEY = config('fcm.server_key');
        $firebaseToken = PersonalAccessToken::whereIn('tokenable_id', $request['peopleIds'])->pluck('fcm_token')->all();

        if (!$firebaseToken) {
            return response()->json(['message' => 'Token empty'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => $request['notification'],
            "data" => $request['data']
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
