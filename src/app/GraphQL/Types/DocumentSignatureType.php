<?php

namespace App\GraphQL\Types;

use App\Enums\SignatureStatusTypeEnum;
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
        $signers = $this->getSigners($rootValue);

        if ($signatures->jumlah_signature > 0) {
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
            'signed_file',
            file_get_contents($data->url),
            $data->file
        )->post(config('sikd.signature_verify_url'));

        return json_decode($response->body());
    }

    /**
     * @param Object $data
     *
     * @return Array
     */
    protected function getSigners($data)
    {
        $people = People::whereIn('PeopleId', function ($query) use ($data) {
            $query->select('PeopleIDTujuan')
                ->from('m_ttd_kirim')
                ->where('status', SignatureStatusTypeEnum::SUCCESS()->value)
                ->where('ttd_id', $data->id)
                ->whereIn('PeopleIDTujuan', $data->documentSignatureSents->pluck('PeopleIDTujuan'));
        })->get();

        return $people;
    }
}
