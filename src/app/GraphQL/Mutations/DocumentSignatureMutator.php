<?php

namespace App\GraphQL\Mutations;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\DocumentSignatureSent;
use App\Models\PassphraseSession;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DocumentSignatureMutator
{
    use SendNotificationTrait;

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

        if ($documentSignatureSent->status != SignatureStatusTypeEnum::WAITING()->value) {
            throw new CustomException('User already signed this document', 'Status of this document is already signed');
        }

        $setupConfig = $this->setupConfigSignature();
        $file = $this->fileExist($documentSignatureSent->documentSignature->url);

        if (!$file) {
            throw new CustomException('Document not found', 'Document signature not found at website server');
        }

        $checkUser = json_decode($this->checkUserSignature($setupConfig));
        if ($checkUser->status_code != 1111) {
            throw new CustomException('Invalid user', 'Invalid credential user, please check your passphrase again');
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
        //flagging for easy define by mobile
        $linkQR = 'document_direct_upload-' . $data->ttd_id;

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $setupConfig['auth'],
            'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
        ])->attach(
            'file', file_get_contents($data->documentSignature->url), $data->documentSignature->file
        )->post($url, [
            'nik' => $setupConfig['nik'],
            'passphrase' => $passphrase,
            'tampilan' => 'visible',
            'page' => '1',
            'image' => 'false',
            'imageTTD' => '',
            'linkQR'=>$linkQR,
            'xAxis'=>10,
            'yAxis'=>10,
            'width'=>80,
            'height'=>80
        ]);

        if ($response->status() != 200) {
            throw new CustomException('Document failed', 'Signature failed, check your file again');
        } else {
            //Save new file & update status
            $data = $this->saveNewFile($response, $data);
            //Save log
            $this->createPassphraseSessionLog($response);
        }

        return $data;
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
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $setupConfig['auth'],
            'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
        ])->get($checkUrl);

        return $response->body();
    }

    /**
     * setupConfigSignature
     *
     * @return array
     */
    protected function setupConfigSignature()
    {
        $setup = [
            'nik' => (config('sikd.enable_sign_with_nik')) ? auth()->user()->NIK : config('sikd.signature_nik'),
            'url' => config('sikd.signature_url'),
            'auth' => config('sikd.signature_auth'),
            'cookies' => config('sikd.signature_cookies'),
        ];

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
     * @param  mixed $response
     * @return void
     */
    protected function createPassphraseSessionLog($response)
    {
        $passphraseSession = new PassphraseSession();
        $passphraseSession->nama_lengkap    = auth()->user()->PeopleName;
        $passphraseSession->jam_akses       = Carbon::now();
        $passphraseSession->keterangan      = 'Insert Passphrase Berhasil, Data disimpan';
        $passphraseSession->log_desc        = 'sukses';

        if ($response->status() != 200) {
            $passphraseSession->keterangan      = 'Insert Passphrase Gagal, Data failed';
            $passphraseSession->log_desc        = 'gagal';
        }

        $passphraseSession->save();

        return $passphraseSession;
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
        //save to storage path for temporary file
        $newFileName = time() .'_signed.pdf';
        Storage::disk('local')->put($newFileName, $pdf->body());

        $fileSignatured = fopen(Storage::path($newFileName), 'r');
        $response = Http::withHeaders([
            'Secret' => config('sikd.webhook_secret'),
        ])->attach(
            'file', $fileSignatured, $newFileName
        )->post(config('sikd.webhook_url'));

        if ($response->status() != 200) {
            throw new CustomException('Webhook failed', json_decode($response));
        } else {
            $data = $this->updateDocumentSentStatus($data, $newFileName);
        }

        Storage::disk('local')->delete($newFileName);

        return $data;
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
            'tgl_ttd' => setDateTimeNowValue()
        ])->first();

        //check if any next siganture require
        $nextDocumentSent = DocumentSignatureSent::where('id', $data->id)
                                                ->where('urutan', $data->urutan + 1);
        if ($nextDocumentSent->first()) {
            $nextDocumentSentId = $nextDocumentSent->id;
            $nextDocumentSent->update(['next', 1]);

            //Send notification to next people
            $this->sendNotification($data, $nextDocumentSentId);
        }

        return $updateDocumentSent;
    }

    /**
     * sendNotification
     *
     * @param  object $data
     * @return void
     */
    protected function sendNotification($data, $nextDocumentSentId)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => 'Ada naskah masuk dari ' . $data->sender->PeopleName . ' yang harus segera di tandatangani. Silahkan cek disini.'
            ],
            'data' => [
                'documentSignatureSentId' => $nextDocumentSentId,
                'target' => DocumentSignatureSentNotificationTypeEnum::RECEIVER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }
}
