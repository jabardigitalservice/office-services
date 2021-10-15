<?php

namespace App\GraphQL\Types;

use App\Models\People;
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
    protected function getSignatures($fileName)
    {
        $filePath   = config('sikd.base_path_file_letter') . $fileName;
        $file       = new \CurlFile($filePath, 'application/pdf', $fileName);

        $headers = [
            'Accept: */*',
            'Content-Type: multipart/form-data',
            'Authorization: Basic ' . config('sikd.signature_auth')
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, config('sikd.signature_verify_url'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['signed_file' => $file]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
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
