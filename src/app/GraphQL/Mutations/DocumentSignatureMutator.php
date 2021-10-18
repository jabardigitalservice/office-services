<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\DocumentSignatureSent;
use App\Models\PassphraseSession;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Storage;

class DocumentSignatureMutator
{
    /**
     * @param $rootValue
     * @param $args
     *
     * @throws \Exception
     *
     * @return array
     */
    public function signature($rootValue, array $args)
    {
        $documentSignatureSentId = Arr::get($args, 'input.documentSignatureSentId');
        $passphrase = Arr::get($args, 'input.passphrase');
        $documentSignatureSent = DocumentSignatureSent::findOrFail($documentSignatureSentId);

        if ($documentSignatureSent->status == 1) {
            throw new CustomException('User already signed this document', 'Status of this document is already signed');
        }

        $setupConfig = $this->setupConfigSignature();
        $file = $this->fileExist($documentSignatureSent->documentSignature->url);

        if (!$file) {
            throw new CustomException('Document not found', 'Document signature not found at website server');
        }

        $checkUser = json_decode($this->checkUserSignature($setupConfig));
        if ($checkUser->status_code != 1111) {
            throw new CustomException('User not found', 'User not found at BSRE Service');
        }

        $signature = $this->doSignature($setupConfig, $documentSignatureSent, $passphrase);
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
		$headers =  [
            'Authorization: Basic ' . $setupConfig['auth'],
            'Cookie: JSESSIONID=' . $setupConfig['cookies'],
			'Content-Type: multipart/form-data',

        ];
        $body= $this->setupBodyRequestSignature($setupConfig, $data, $passphrase);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        //Save log
        $this->createPassphraseSessionLog($httpStatusCode);
        //Save new file & update status
        $newFile = $this->saveNewFile($response, $data);
        return $newFile;
    }

    /**
     * checkUserSignature
     *
     * @param  array $setupConfig
     * @return string
     */
    protected function checkUserSignature($setupConfig)
    {
        $checkUrl = $setupConfig['url'] . '/api/user/status/' . $setupConfig['nik'];
        $client = new GuzzleClient();
        $r = $client->request('GET', $checkUrl, [
            'headers' => [
                'Authorization' => 'Basic ' . $setupConfig['auth'],
                'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
            ]
        ]);

        $response = $r->getBody()->getContents();

        return $response;
    }

    /**
     * setupConfigSignature
     *
     * @return array
     */
    protected function setupConfigSignature()
    {
        $env = config('app.env');
        $setup = [
            'nik' => config('sikd.signature_nik'),
            'url' => config('sikd.signature_url'),
            'auth' => config('sikd.signature_auth'),
            'cookies' => config('sikd.signature_cookies'),
        ];

        //check if production, set with auth data
        if ($env == 'production') {
            $setup['nik'] = auth()->user()->NIK;
        }

        return $setup;
    }

    /**
     * fileExist
     *
     * @param  mixed $url
     * @return void
     */
    public function fileExist($url){
        $headers = get_headers($url);
        return stripos($headers[0],"200 OK") ? true : false;
    }

    /**
     * createPassphraseSessionLog
     *
     * @param  mixed $httpStatusCode
     * @return void
     */
    protected function createPassphraseSessionLog($httpStatusCode)
    {
        $passphraseSession = new PassphraseSession();
        $passphraseSession->nama_lengkap    = auth()->user()->PeopleName;
        $passphraseSession->jam_akses       = Carbon::now();
        $passphraseSession->keterangan      = 'Insert Passphrase Berhasil, Data disimpan';
        $passphraseSession->log_desc        = 'sukses';

        if ($httpStatusCode != 200) {
            $passphraseSession->keterangan      = 'Insert Passphrase Gagal, Data failed';
            $passphraseSession->log_desc        = 'gagal';
        }

        $passphraseSession->save();
        if ($httpStatusCode != 200) {
            throw new CustomException('Signature failed', 'Signature failed, check your passpharse or file');
        }

        return $passphraseSession;
    }

    /**
     * setupBodyRequestSignature
     *
     * @param  array $setupConfig
     * @param  collection $data
     * @param  string $passphrase
     * @return array
     */
    protected function setupBodyRequestSignature($setupConfig, $data, $passphrase)
    {
        $linkQR = config('sikd.url') . 'administrator/tandatangan/verifikasi/' . $data->ttd_id;
        $body = [
            'file' => $this->cURLFile($data->documentSignature->url, $data->documentSignature->file),
            'nik' => $setupConfig['nik'],
            'passphrase' => $passphrase,
            'tampilan' => 'invisible',
            'page' => '1',
            'image' => 'false',
            'imageTTD' => '',
            'linkQR' => $linkQR,
            'xAxis' => 0,
            'yAxis' => 0,
            'width' => '0',
            'height' => '0'
        ];

        return $body;
    }

    /**
     * saveNewFile
     *
     * @param  object $response
     * @param  collection $data
     * @return collection
     */
    protected function saveNewFile($pdf, $data)
    {
        //save to storage path
        $time = time();
        $newFileName = $time .'_signed.pdf';
        $newPathFile = storage_path('app') . '/' . $newFileName;
        file_put_contents($newPathFile, $pdf);

        $url = config('sikd.webhook_url') . 'file_signatured';
        $headers =  [
            'Secret: ' . config('sikd.webhook_secret'),
			'Content-Type: multipart/form-data',
        ];
        $body = ['file' => $this->cURLFile($newPathFile, $newFileName)];

        list($response, $httpStatusCode) = $this->cURLPost($url, $headers, $body);

        if ($httpStatusCode != 200) {
            throw new CustomException('Webhook failed', json_decode($response));
        }

        unlink($newPathFile);

        $updateDocumentSent = $this->updateDocumentSentStatus($data, $newFileName);

        return $updateDocumentSent;
    }

    /**
     * updateDocumentSentStatus
     *
     * @param  collection $data
     * @param  string $newFileName
     * @return collection
     */
    protected function updateDocumentSentStatus($data, $newFileName)
    {
        //change filename with _signed & update stastus
        $updateFileData = DocumentSignature::where('id', $data->ttd_id)->update([
            'status' => 1,
            'file' => $newFileName
        ]);

        //update status document sent to 1 (signed)
        $updateDocumentSent = tap(DocumentSignatureSent::where('id', $data->id))->update([
            'status' => 1,
            'next' => 1,
            'tgl_ttd' => Carbon::now()
        ])->first();

        //check if any next siganture require
        $nextDocumentSent = DocumentSignatureSent::where('id', $data->id)
                                                ->where('urutan', $data->urutan + 1);
        if ($nextDocumentSent->first()) {
            $nextDocumentSent->update(['next', 1]);
        }

        return $updateDocumentSent;
    }

    /**
     * cURLPost
     *
     * @param  string $url
     * @param  array $headers
     * @param  array $body
     * @return array
     */
    protected function cURLPost($url, $headers, $body)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return [$response, $httpStatusCode];
    }

    /**
     * cURLFile
     *
     * @param  string $file
     * @param  string $name
     * @return void
     */
    protected function cURLFile($file, $name)
    {
        return new \CURLFile($file, 'application/pdf', $name);
    }
}
