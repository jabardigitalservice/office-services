<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use function PHPUnit\Framework\throwException;

class SendNotificationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $SERVER_API_KEY = config('fcm.server_key');
        $firebaseToken = PersonalAccessToken::whereIn('tokenable_id', $request->peopleIds)->pluck('fcm_token')->all();

        if (!$firebaseToken) {
            return response()->json(['message' => 'Token empty'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                'title' => $request->sender,
                'body' => $request->about . '\n Tingka Urgensi: ' . $request->urgencyName,
            ],
            "data" => [
                'id' => $request->inboxId,
                'source' => $request->source,
                'date' => $request->date,
            ]
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
