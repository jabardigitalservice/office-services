<?php

namespace App\GraphQL\Types;

use App\Models\People;
use Illuminate\Support\Facades\Http;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DocumentSignatureType
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
        $signatures = $this->getSignatures($rootValue);

        $isValid = false;
        $signers = $this->getSigners($signatures);

        if (count($signers) != 0) {
            $isValid = true;
        }

        $validation = [
            'isValid' => $isValid,
            'signatures' => $signers
        ];

        return $validation;
    }

    /**
     * @param String $fileName
     *
     * @return Object
     */
    protected function getSignatures($data)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . config('sikd.signature_auth'),
        ])->attach(
            'signed_file', file_get_contents($data->url), $data->file
        )->post(config('sikd.signature_verify_url'));

        return json_decode($response->body());
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
