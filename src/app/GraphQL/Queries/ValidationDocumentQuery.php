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
                $code = $this->getValidationByQRCode($args);
                break;

            case ValidationDocumentTypeEnum::CODE():
                $code = $args['filter']['value'];
                break;

            default:
                throw new CustomException(
                    'Type Not Available',
                    'Type of Validation Document not available, please try again later'
                );
                break;
        }

        return $this->getValidationByCode($code);
    }

    /**
     * getValidationByQRCode
     *
     * @param  array $args
     * @return array
     */
    private function getValidationByQRCode($args)
    {
        $splitValue = explode('/', $args['filter']['value']);
        $latestSlug = end($splitValue);
        $codeFromUrl = explode('?', $latestSlug);

        return reset($codeFromUrl);
    }

    /**
     * getValidationByCode
     *
     * @param  mixed $args
     * @return void
     */
    private function getValidationByCode($code)
    {
        $inboxFile = InboxFile::where('id_dokumen', $code)->first();
        $documentSignature = null;

        if (!$inboxFile) {
            $documentSignature = DocumentSignature::where('code', $code)->first();
        }

        $data = collect([
            'documentSignature' => $documentSignature,
            'inboxFile' => $inboxFile
        ]);

        return $data;
    }
}
