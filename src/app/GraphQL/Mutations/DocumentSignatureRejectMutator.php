<?php

namespace App\GraphQL\Mutations;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Exceptions\CustomException;
use App\Models\DocumentSignatureSent;
use Illuminate\Support\Arr;

class DocumentSignatureRejectMutator
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
    public function reject($rootValue, array $args)
    {
        $documentSignatureSentId = Arr::get($args, 'input.documentSignatureSentId');
        $note = Arr::get($args, 'input.note');

        $documentSignatureSent = tap(DocumentSignatureSent::where('id', $documentSignatureSentId))->update([
            'status' => SignatureStatusTypeEnum::REJECT()->value,
            'catatan' => $note,
            'tgl' => setDateTimeNowValue()
        ])->first();

        if (!$documentSignatureSent) {
            throw new CustomException(
                'Document not found',
                'Docuement with this id not found'
            );
        }

        return $documentSignatureSent;
    }

    /**
     * sendNotification
     *
     * @param  object $data
     * @return void
     */
    protected function sendNotification($data)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => 'Ada naskah yang tidak berhasil ditandatangani. Silahkan klik disini untuk mengecek alasannya.',
            ],
            'data' => [
                'documentSignatureSentId' => $data['id'],
                'target' => DocumentSignatureSentNotificationTypeEnum::SENDER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }
}
