<?php

namespace App\GraphQL\Mutations;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Http\Traits\SignatureTrait;
use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\DocumentSignatureForward;
use App\Models\DocumentSignatureSent;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DocumentSignatureMutator
{
    use SendNotificationTrait;
    use SignatureTrait;

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

        $checkParent = DocumentSignatureSent::where('ttd_id', $documentSignatureSent->ttd_id)
            ->where('urutan', $documentSignatureSent->urutan - 1)
            ->first();

        if ($checkParent && $checkParent->status != SignatureStatusTypeEnum::SUCCESS()->value) {
            throw new CustomException('Parent user is not already signed this document', 'Parent user of list signature assign is not already signed');
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
        $newFileName = str_replace(' ', '_', $data->documentSignature->nama_file) . '_' . parseDateTimeFormat(Carbon::now(), 'dmY')  . '_signed.pdf';
        $verifyCode = strtoupper(substr(sha1(uniqid(mt_rand(), true)), 0, 10));
        if ($data->urutan == 1) {
            $pdfFile = $this->addFooterDocument($data, $newFileName, $verifyCode);
        } else {
            $pdfFile = file_get_contents($data->documentSignature->url);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $setupConfig['auth'],
            'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
        ])->attach(
            'file',
            $pdfFile,
            $data->documentSignature->file
        )->post($url, [
            'nik'           => $setupConfig['nik'],
            'passphrase'    => $passphrase,
            'tampilan'      => 'invisible',
            'image'         => 'false',
        ]);

        if ($response->status() != Response::HTTP_OK) {
            throw new CustomException('Document failed', 'Signature failed, check your file again');
        } else {
            //Save new file & update status
            $data = $this->saveNewFile($response, $data, $newFileName, $verifyCode);
            //Save log
            $this->createPassphraseSessionLog($response);
        }

        return $data;
    }

    /**
     * fileExist
     *
     * @param  mixed $url
     * @return void
     */
    public function fileExist($url)
    {
        $headers = get_headers($url);
        return stripos($headers[0], "200 OK") ? true : false;
    }

    /**
     * saveNewFile
     *
     * @param  object $response
     * @param  collection $data
     * @return collection
     */
    protected function saveNewFile($pdf, $data, $newFileName, $verifyCode)
    {
        //save to storage path for temporary file
        Storage::disk('local')->put($newFileName, $pdf->body());

        $fileSignatured = fopen(Storage::path($newFileName), 'r');
        $response = Http::withHeaders([
            'Secret' => config('sikd.webhook_secret'),
        ])->attach(
            'signature',
            $fileSignatured,
            $newFileName
        )->post(config('sikd.webhook_url'));

        if ($response->status() != Response::HTTP_OK) {
            throw new CustomException('Webhook failed', json_decode($response));
        } else {
            $data = $this->updateDocumentSentStatus($data, $newFileName, $verifyCode);
            $this->forward($data);
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
    protected function updateDocumentSentStatus($data, $newFileName, $verifyCode)
    {
        //change filename with _signed & update stastus
        $updateFileData = DocumentSignature::where('id', $data->ttd_id)->update([
            'status' => 1,
            'file' => $newFileName,
            'code' => $verifyCode
        ]);

        //update status document sent to 1 (signed)
        $updateDocumentSent = tap(DocumentSignatureSent::where('id', $data->id))->update([
            'status' => 1,
            'next' => 1,
            'tgl_ttd' => setDateTimeNowValue()
        ])->first();

        //check if any next siganture require
        $nextDocumentSent = DocumentSignatureSent::where('ttd_id', $data->ttd_id)
                                                ->where('urutan', $data->urutan + 1)
                                                ->first();
        if ($nextDocumentSent) {
            DocumentSignatureSent::where('id', $nextDocumentSent->id)->update([
                'next' => 1
            ]);

            //Send notification to next people
            $this->doSendNotification($nextDocumentSent->id);
        }

        return $updateDocumentSent;
    }

    /**
     * doSendNotification
     *
     * @param  object $data
     * @return void
     */
    protected function doSendNotification($nextDocumentSentId)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => 'Terdapat naskah masuk untuk segera Anda tanda tangani secara digital. Klik disini untuk membaca dan menindaklanjuti pesan.'
            ],
            'data' => [
                'documentSignatureSentId' => $nextDocumentSentId,
                'target' => DocumentSignatureSentNotificationTypeEnum::RECEIVER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }

    /**
     * addFooterDocument
     *
     * @param  mixed $data
     * @param  mixed $newFileName
     * @return void
     */
    protected function addFooterDocument($data, $newFileName, $verifyCode)
    {
        $addFooter = Http::post(config('sikd.add_footer_url'), [
            'pdf' => 'https://devsimanis.jabarprov.go.id/api/mobile/api/v1/draft/218180322035035',
            'qrcode' => config('sikd.url') . '/FilesUploaded/ttd/sudah_ttd/' . $newFileName,
            'category' => $data->documentSignature->documentSignatureType->document_paper_type,
            'code' => $verifyCode
        ]);

        return $addFooter;
    }

    protected function forward($documentSignatureSent)
    {
        $nextDocument = DocumentSignatureSent::where('id', $documentSignatureSent->id)
                                            ->where('urutan', $documentSignatureSent->urutan + 1)
                                            ->first();

        if (!$nextDocument) {
            $documentSignatureForwardIds = $this->doForward($documentSignatureSent);

            if (!$documentSignatureForwardIds) {
                throw new CustomException(
                    'Forward document failed',
                    'Return ids is missing. Please try again.'
                );
            }

            $this->doSendForwardNotification($documentSignatureSent->id, $documentSignatureSent->receiver->PeopleName);
        }

        return $documentSignatureForwardIds;
    }

    /**
     * doSendForwardNotification
     *
     * @param  string $id
     * @param  string $name
     * @return void
     */
    protected function doSendForwardNotification($id, $name)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => 'Naskah Anda telah di tandocumentSignatureSentngani oleh ' . $name . '. Klik disini untuk lihat naskah!',
            ],
            'data' => [
                'documentSignatureSentId' => $id,
                'target' => DocumentSignatureSentNotificationTypeEnum::SENDER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }

    /**
     * doForward
     *
     * @param  object $documentSignatureSentId
     * @param  string $sender
     * @param  mixed $args
     * @return array
     */
    public function doForward($documentSignatureSent)
    {
        $ids = array();
        $receiver = $this->forwardReceiver($documentSignatureSent);

        if ($receiver != null) {
            foreach ($receiver as $key => $receiver) {
                $key++;
                $documentSignatureForward = DocumentSignatureForward::create([
                    'ttd_id' => $documentSignatureSent->ttd_id,
                    'catatan' => '',
                    'tgl' => Carbon::now(),
                    'PeopleID' => $documentSignatureSent->PeopleIDTujuan,
                    'PeopleIDTujuan' => $receiver,
                    'urutan' => $key,
                    'status' => SignatureStatusTypeEnum::WAITING()->value,
                ]);

                array_push($ids, $documentSignatureForward);
            }

            return $ids;
        }

        return false;
    }

    /**
     * forwardReceiver
     *
     * @param  mixed $type
     * @return mixed
     */
    public function forwardReceiver($documentSignatureSent)
    {
        $type = optional($documentSignatureSent->documentSignature->documentSignatureType)->document_forward_target;
        switch ($type) {
            case 'UK':
            case 'TU':
                if ($type == 'UK') {
                    $peopleGroupType = PeopleGroupTypeEnum::UK()->value;
                    $whereField = 'GRoleId';
                    $whereParams = auth()->user()->role->GRoleId;
                }
                if ($type == 'TU') {
                    $peopleGroupType = PeopleGroupTypeEnum::TU()->value;
                    $whereField = 'Code_Tu';
                    $whereParams = auth()->user()->role->Code_Tu;
                }
                $receiver = People::whereHas('role', function ($role) use ($whereField, $whereParams) {
                    $role->where('RoleCode', auth()->user()->role->RoleCode);
                    $role->where($whereField, $whereParams);
                })->where('GroupId', $peopleGroupType)->pluck('PeopleId');
                break;

            default:
                $receiver = null;
                break;
        }

        return $receiver;
    }
}
