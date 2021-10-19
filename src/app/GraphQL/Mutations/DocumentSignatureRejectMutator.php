<?php

namespace App\GraphQL\Mutations;

use App\Enums\SignatureStatusTypeEnum;
use App\Exceptions\CustomException;
use App\Models\DocumentSignatureSent;
use Illuminate\Support\Arr;

class DocumentSignatureRejectMutator
{
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
}
