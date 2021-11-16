<?php

namespace App\GraphQL\Mutations;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Exceptions\CustomException;
use App\Models\DocumentSignatureForward;
use App\Models\DocumentSignatureSent;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class DocumentSignatureForwardMutator
{
    use SendNotificationTrait;

    /**
     * @param $rootValue
     * @param $args
     *
     * @throws \Exception
     *
     * @return array
     */
    public function forward($rootValue, array $args)
    {
        $documentSignatureId = Arr::get($args, 'input.documentSignatureId');
        $sender = Arr::get($args, 'input.sender');

        $documentSignatureForwardIds = $this->doForward($documentSignatureId, $sender, $args);

        if (!$documentSignatureForwardIds) {
            throw new CustomException(
                'Forward document failed',
                'Return ids is missing. Please try again.'
            );
        }

        $data = DocumentSignatureSent::where('ttd_id', $documentSignatureId)
                                    ->where('PeopleIDTujuan', $sender)
                                    ->first();

        $this->doSendNotification($data->id, $data->receiver->PeopleName);

        return $documentSignatureForwardIds;
    }

    /**
     * doSendNotification
     *
     * @param  string $id
     * @param  string $name
     * @return void
     */
    protected function doSendNotification($id, $name)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => 'Naskah Anda telah di tandatangani oleh ' . $name . '. Klik disini untuk lihat naskah!',
            ],
            'data' => [
                'documentSignatureSentId' => $id,
                'target' => DocumentSignatureSentNotificationTypeEnum::SENDER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }

    /**
     * doForward
     *
     * @param  string $documentSignatureId
     * @param  string $sender
     * @param  mixed $args
     * @return array
     */
    public function doForward($documentSignatureId, $sender, $args)
    {
        $receivers = Arr::get($args, 'input.receivers');
        $arrayReceivers = explode(", ", $receivers);
        $note = Arr::get($args, 'input.note');
        $ids = array();

        foreach ($arrayReceivers as $key => $receiver) {
            $key++;
            $documentSignatureForward = DocumentSignatureForward::create([
                'ttd_id' => $documentSignatureId,
                'catatan' => $note,
                'tgl' => Carbon::now(),
                'PeopleID' => $sender,
                'PeopleIDTujuan' => $receiver,
                'urutan' => $key,
                'status' => SignatureStatusTypeEnum::WAITING()->value,
            ]);

            array_push($ids, $documentSignatureForward);
        }

        return $ids;
    }
}
