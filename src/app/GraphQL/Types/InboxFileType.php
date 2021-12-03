<?php

namespace App\GraphQL\Types;

use App\Models\People;
use Illuminate\Support\Facades\Http;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InboxFileType
{
    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return array
     */
    public function validate($rootValue, array $args, GraphQLContext $context)
    {
        $fileName = $rootValue->FileName_fake;

        $signatures = $this->getSignatures($fileName);
        if (property_exists($signatures, 'error') || $signatures->jumlah_signature == 0) {
            return [
                'isValid' => false,
                'signatures' => null
            ];
        };

        $signers = $this->getSigners($signatures);

        $validation = [
            'isValid' => true,
            'signatures' => $signers
        ];

        return $validation;
    }

    /**
     * @param String $fileName
     *
     * @return Object
     */
    protected function getSignatures($fileName)
    {
        $filePath   = config('sikd.base_path_file_letter') . $fileName;
        $file       = fopen($filePath, 'r');

        $response = Http::withHeaders([
            'Accept' => '*/*',
            'Authorization' => 'Basic ' . config('sikd.signature_auth'),
        ])->attach(
            'signed_file',
            $file,
            $fileName
        )->post(config('sikd.signature_verify_url'));

        return json_decode($response);
    }

    /**
     * @param Object $signaturesDetails
     *
     * @return Array
     */
    protected function getSigners($signaturesDetails)
    {
        $signatures = $signaturesDetails->details;
        $regex = "/=.[0-9]+/i";

        $signersIds = [];
        foreach ($signatures as $signature) {
            $signer = $signature->info_signer->signer_dn;
            preg_match($regex, $signer, $rawSignerId);
            $signerId = explode("=", $rawSignerId[0])[1];
            array_push($signersIds, $signerId);
        }

        $signers = People::whereIn('NIP', $signersIds)->get();

        return $signers;
    }
}
