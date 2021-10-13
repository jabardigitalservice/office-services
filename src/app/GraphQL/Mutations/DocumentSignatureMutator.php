<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\DocumentSignatureSent;
use App\Models\PassphraseSession;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use GuzzleHttp\Client as GuzzleClient;
use League\CommonMark\Block\Element\Document;

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
        $documentSignatureSentId = Arr::get($args, 'input.id');
        $passphrase = Arr::get($args, 'input.passphrase');
        $documentSignatureSent = DocumentSignatureSent::findOrFail($documentSignatureSentId);
        $setupConfig = $this->setupConfigSignature();

        $file = $this->fileExist($documentSignatureSent->documentSignature->url);

        if (!$file) {
            throw new CustomException(
                'Document not found',
                'Document signature not found at website server'
            );
        }

        $checkUser = json_decode($this->checkUserSignature($setupConfig));

        if ($checkUser->status_code != 1111) {
            throw new CustomException(
                'User not found',
                'User not found at BSRE Service'
            );
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
    public function doSignature($setupConfig, $data, $passphrase)
    {
        $signUrl = $setupConfig['url'] . '/api/sign/pdf';
        $ch = curl_init();

		$headers =  [
            'Authorization: Basic ' . $setupConfig['auth'],
            'Cookie: JSESSIONID=' . $setupConfig['cookies'],
			'Content-Type: multipart/form-data',

        ];

        $linkQR = config('sikd.base_url') . 'administrator/tandatangan/verifikasi/' . $data->ttd_id;

        $body = [
            'file' => new \CURLFile(config('sikd.server_path_file') . $data->documentSignature->folder_url . $data->documentSignature->file, 'application/pdf', $data->documentSignature->file),
            'nik' => $setupConfig['nik'],
            'passphrase' => $passphrase,
            'tampilan' => 'invisible',
            'page' => '1',
            'image' => 'false',
            'imageTTD' => '',
            'linkQR' => $linkQR,
            'xAxis' => 0,
            'yAxis' => 0,
            'width' => '200',
            'height' => '100'
        ];

        curl_setopt($ch, CURLOPT_URL, $signUrl);
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

        $time = time();
        $newFilePath = config('sikd.server_path_file') . 'ttd/sudah_ttd/' . $time . '_signed.pdf';
        $newFileName = $time .'_signed.pdf';

        file_put_contents($newFilePath, $response);

        $updateFileData = DocumentSignature::where('id', $data->ttd_id)->update([
            'status' => 1,
            'file' => $newFileName
        ]);

        $updateDocumentSent = tap(DocumentSignatureSent::where('id', $data->id))->update([
            'status' => 1,
            'next' => 1,
            'tgl_ttd' => Carbon::now()
        ])->first();

        $nextDocumentSent = DocumentSignatureSent::where('id', $data->id)
                                                ->where('urutan', $data->urutan + 1);
        if ($nextDocumentSent->first()) {
            $nextDocumentSent->update(['next', 1]);
        }

        return $updateDocumentSent;
    }

    /**
     * checkUserSignature
     *
     * @param  array $setupConfig
     * @return string
     */
    public function checkUserSignature($setupConfig)
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
    public function setupConfigSignature()
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
    public function createPassphraseSessionLog($httpStatusCode)
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

        return $passphraseSession;
    }
}
