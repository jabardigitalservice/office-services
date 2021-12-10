<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Http\Traits\DraftTrait;
use App\Http\Traits\SignatureTrait;
use App\Models\Draft;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class DraftSignatureMutator
{
    use DraftTrait;
    use SignatureTrait;

    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function signature($rootValue, array $args)
    {
        $draftId = Arr::get($args, 'input.draftId');
        $passphrase = Arr::get($args, 'input.passphrase');
        $draft = Draft::where('NId_temp', $draftId)->first();

        if ($draft->Konsep == 0) {
            throw new CustomException('Document already signed', 'Status of this document is already signed');
        }

        $setupConfig = $this->setupConfigSignature();
        $checkUser = json_decode($this->checkUserSignature($setupConfig));
        if ($checkUser->status_code != 1111) {
            throw new CustomException('Invalid user', 'Invalid credential user, please check your passphrase again');
        }

        $signature = $this->doSignature($setupConfig, $draft, $passphrase);
        return $signature;
    }

    /**
     * doSignature
     *
     * @param  array $setupConfig
     * @param  collection $data
     * @param  string $passphrase
     * @return collection
     */
    protected function doSignature($setupConfig, $data, $passphrase)
    {
        $url = $setupConfig['url'] . '/api/sign/pdf';
        $verifyCode = substr(sha1(uniqid(mt_rand(), TRUE)), 0, 10);
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $setupConfig['auth'],
            'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
        ])->attach(
            'file',
            $this->setDraftDocumentPdf($data->NId_Tem, $verifyCode),
            $data->document_file_name
        )->post($url, [
            'nik'           => $setupConfig['nik'],
            'passphrase'    => $passphrase,
            'tampilan'      => 'invisible',
            'page'          => '1',
            'image'         => 'false',
        ]);

        if ($response->status() != 200) {
            throw new CustomException('Document failed', 'Signature failed, check your file again');
        } else {
            // //Save new file & update status
            // $data = $this->saveNewFile($response, $data);
            // //Save log
            // $this->createPassphraseSessionLog($response);
        }

        return $data;
    }
}
