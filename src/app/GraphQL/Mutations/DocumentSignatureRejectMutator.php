<?php

namespace App\GraphQL\Mutations;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use App\Enums\StatusReadTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
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
            'tgl' => setDateTimeNowValue(),
            'is_sender_read' => false
        ])->first();

        if (!$documentSignatureSent) {
            throw new CustomException(
                'Document not found',
                'Docuement with this id not found'
            );
        }

        DocumentSignature::where('id', $documentSignatureSent->ttd_id)->update([
            'status' => SignatureStatusTypeEnum::REJECT()->value,
        ]);

        $this->doSendNotification($documentSignatureSentId);

        return $documentSignatureSent;
    }

    /**
     * doSendNotification
     *
     * @param  object $data
     * @return void
     */
    protected function doSendNotification($documentSignatureSentId)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'Penolakan TTE Naskah',
                'body' => 'Ada naskah yang tidak berhasil ditandatangani. Silakan klik disini untuk mengecek alasannya.',
            ],
            'data' => [
                'documentSignatureSentId' => $documentSignatureSentId,
                'target' => DocumentSignatureSentNotificationTypeEnum::SENDER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }
}
