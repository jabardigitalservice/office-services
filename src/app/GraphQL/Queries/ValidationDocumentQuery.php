<?php

namespace App\GraphQL\Queries;

use App\Enums\ValidationDocumentTypeEnum;
use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\InboxFile;

class ValidationDocumentQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args)
    {
        switch ($args['filter']['type']) {
            case ValidationDocumentTypeEnum::QRCODE():
                return $this->getValidationByQRCode($args);
                break;

            case ValidationDocumentTypeEnum::CODE():
                # code...
                break;

            default:
                throw new CustomException(
                    'Type Not Available',
                    'Type of Validation Document not available, please try again later'
                );
                break;
        }
    }

    private function getValidationByQRCode($args)
    {
        $splitValue = explode('/', $args['filter']['value']);
        $nameFile = end($splitValue);

        $documentSignature = DocumentSignature::where('file', $nameFile)->first();
        $inboxFile = InboxFile::where('FileName_fake', $nameFile)->first();

        $data = collect([
            'documentSignature' => $documentSignature,
            'inboxFile' => $inboxFile
        ]);

        return $data;
    }
}
