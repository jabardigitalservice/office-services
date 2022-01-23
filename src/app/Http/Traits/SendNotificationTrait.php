<?php

namespace App\Http\Traits;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\FcmNotificationActionTypeEnum;
use App\Models\DocumentSignatureSent;
use App\Models\InboxReceiver;
use App\Models\InboxReceiverCorrection;
use Illuminate\Support\Facades\Http;

/**
 * Send notification to mobile device using firebase cloud messaging
 */
trait SendNotificationTrait
{
    public function setupInboxReceiverNotification($request)
    {
        $action = $request['data']['action'];
        switch ($action) {
            case FcmNotificationActionTypeEnum::DRAFT_DETAIL():
            case FcmNotificationActionTypeEnum::DRAFT_REVIEW():
                $inboxReceiverModel = InboxReceiverCorrection::whereIn('To_Id', $request['data']['peopleIds']);
                break;

            default:
                $inboxReceiverModel = InboxReceiver::whereIn('To_Id', $request['data']['peopleIds']);
                break;
        }
        $inboxReceiver = $inboxReceiverModel->where('NId', $request['data']['inboxId'])
                                    ->where('GIR_Id', $request['data']['groupId'])
                                    ->with('personalAccessTokens')
                                    ->get();
        if (!$inboxReceiver) {
            return false;
        }

        foreach ($inboxReceiver as $message) {
            $token = $message->personalAccessTokens->pluck('fcm_token');
            $messageAttribute = $this->setNotificationAttribute($token, $request, $message, $action);
            $this->sendNotification($messageAttribute);
        }

        return true;
    }

    /**
     * setupDocumentSignatureSentNotification
     *
     * @param  mixed $request
     * @return boolean
     */
    public function setupDocumentSignatureSentNotification($request)
    {
        list($data, $token) = $this->setDocumentSignatureSentTarget($request);

        if (!$data) {
            return false;
        }

        $messageAttribute = $this->setNotificationAttribute(
            $token,
            $request,
            $data,
            FcmNotificationActionTypeEnum::DOC_SIGNATURE_DETAIL()
        );
        $send = $this->sendNotification($messageAttribute);

        return true;
    }

    /**
     * setDocumentSignatureSentTarget
     *
     * @param  object $request
     * @return array
     */
    public function setDocumentSignatureSentTarget($request)
    {
        $documentSignatureSent = DocumentSignatureSent::where('id', $request['data']['documentSignatureSentId']);
        if ($request['data']['target'] == DocumentSignatureSentNotificationTypeEnum::SENDER()) {
            $documentSignatureSent->with('senderPersonalAccessTokens');
        }

        if ($request['data']['target'] == DocumentSignatureSentNotificationTypeEnum::RECEIVER()) {
            $documentSignatureSent->with('receiverPersonalAccessTokens');
        }

        $data = $documentSignatureSent->first();

        if (!$data) {
            return [false, false];
        }

        if ($request['data']['target'] == DocumentSignatureSentNotificationTypeEnum::SENDER()) {
            $token = $data->senderPersonalAccessTokens->pluck('fcm_token');
        }

        if ($request['data']['target'] == DocumentSignatureSentNotificationTypeEnum::RECEIVER()) {
            $token = $data->receiverPersonalAccessTokens->pluck('fcm_token');
        }

        return [$data, $token];
    }

    /**
     * setNotificationAttribute
     *
     * @param  array $token
     * @param  array $request
     * @param  object $record
     * @param  enum $action
     * @return array
     */
    public function setNotificationAttribute($token, $request, $record, $action)
    {
        $messageAttribute = [
            'registration_ids' => $token,
            'notification' => $request['notification'],
            'data' => [
                'id' => $record->id,
                'action' => $action
            ]
        ];

        if (
            $action == FcmNotificationActionTypeEnum::DRAFT_DETAIL() ||
            $action == FcmNotificationActionTypeEnum::DRAFT_REVIEW()
        ) {
            $messageAttribute['data'] = [
                'draftId' => $record->NId,
                'groupId' => $record->GIR_Id,
                'receiverAs' => $record->ReceiverAs,
                'letterNumber' => $record->draftDetail->nosurat,
                'draftStatus' => $record->draftDetail->Konsep,
                'action' => $action
            ];
        }

        return $messageAttribute;
    }

    /**
     * sendNotification
     *
     * @param  mixed $request
     * @return object
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
        ])->post(config('fcm.url'), $data);

        return json_decode($response->body());
    }
}
