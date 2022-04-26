<?php

namespace App\GraphQL\Types;

use App\Enums\SignatureStatusTypeEnum;
use App\Models\People;
use Illuminate\Http\Response;
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
        $signaturesResponse = json_decode($signatures);
        dd($signaturesResponse);
        if ($signatures->status() != Response::HTTP_OK || property_exists($signaturesResponse, 'error') || $signaturesResponse->jumlah_signature == 0) {
            return [
                'isValid' => false,
                'signatures' => null
            ];
        };

        $signers = $this->getSigners($signaturesResponse, $rootValue);

        $validation = [
            'isValid' => true,
            'signatures' => $signers
        ];

        return $validation;
    }

    /**
     * @param String $fileName
     *
     * @return Mixed
     */
    protected function getSignatures($data)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . config('sikd.signature_auth'),
        ])->attach(
            'signed_file',
            file_get_contents($data->url),
            $data->file
        )->post(config('sikd.signature_verify_url'));

        return $response;
    }

    /**
     * @param Object $data
     *
     * @return Array
     */
    protected function getSigners($signaturesDetails, $data)
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

        if ($signers->isEmpty()) {
            $signers = $this->getSignersByData($data);
        }

        return $signers;
    }

    /**
     * getSignersByData
     *
     * @param  mixed $data
     * @return void
     */
    protected function getSignersByData($data)
    {
        $signers = People::whereIn('PeopleId', function ($query) use ($data) {
            $query->select('PeopleIDTujuan')
                ->from('m_ttd_kirim')
                ->where('status', SignatureStatusTypeEnum::SUCCESS()->value)
                ->where('ttd_id', $data->id)
                ->whereIn('PeopleIDTujuan', $data->documentSignatureSents->pluck('PeopleIDTujuan'));
        })->get();

        if ($data->is_signed_self == true) {
            $selfSigned = People::where('PeopleId', $data->PeopleID)->get();
            if (count($signers) > 0) {
                $signers = $signers->merge($selfSigned);
            } else {
                $signers = $selfSigned;
            }
        }

        return $signers;
    }
}
