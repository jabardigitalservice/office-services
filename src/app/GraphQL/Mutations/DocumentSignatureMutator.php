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
        $newFileName = $data->documentSignature->document_file_name;
        $verifyCode = strtoupper(substr(sha1(uniqid(mt_rand(), true)), 0, 10));
        $pdfFile = $this->pdfFile($data, $newFileName, $verifyCode);

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
            $bodyResponse = json_decode($response->body());
            throw new CustomException('Signature failed', $bodyResponse->error);
        } else {
            //Save new file & update status
            $data = $this->saveNewFile($response, $data, $newFileName, $verifyCode);
        }
        //Save log
        $this->createPassphraseSessionLog($response);

        return $data;
    }

    /**
     * pdfFile
     *
     * @param  mixed $data
     * @param  mixed $newFileName
     * @param  mixed $verifyCode
     * @return void
     */
    protected function pdfFile($data, $newFileName, $verifyCode)
    {
        if ($data->documentSignature->has_footer == false) {
            $pdfFile = $this->addFooterDocument($data, $verifyCode);
        } else {
            $pdfFile = file_get_contents($data->documentSignature->url);
        }

        return $pdfFile;
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

        try {
            //transfer to existing service
            $response = $this->doTransferFile($newFileName);
            if ($response->status() != Response::HTTP_OK) {
                throw new CustomException('Webhook failed', json_decode($response));
            } else {
                $data = $this->updateDocumentSentStatus($data, $newFileName, $verifyCode);
            }
        } catch (\Throwable $th) {
            throw new CustomException('Connect API for webhook store file failed', $th->getMessage());
        }

        Storage::disk('local')->delete($newFileName);

        return $data;
    }

    /**
     * doTransferFile
     *
     * @param  string $newFileName
     * @return mixed
     */
    public function doTransferFile($newFileName)
    {
        $fileSignatured = fopen(Storage::path($newFileName), 'r');
        $response = Http::withHeaders([
            'Secret' => config('sikd.webhook_secret'),
        ])->attach(
            'signature',
            $fileSignatured,
            $newFileName
        )->post(config('sikd.webhook_url'));

        return $response;
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
        if ($data->documentSignature->has_footer == false) {
            $updateFileData = DocumentSignature::where('id', $data->ttd_id)->update([
                'status' => SignatureStatusTypeEnum::SUCCESS()->value,
                'file' => $newFileName,
                'code' => $verifyCode,
                'has_footer' => true,
            ]);
        } else {
            $updateFileData = DocumentSignature::where('id', $data->ttd_id)->update([
                'status' => SignatureStatusTypeEnum::SUCCESS()->value,
            ]);
        }

        //update status document sent to 1 (signed)
        $updateDocumentSent = tap(DocumentSignatureSent::where('id', $data->id))->update([
            'status' => SignatureStatusTypeEnum::SUCCESS()->value,
            'next' => 1,
            'tgl_ttd' => setDateTimeNowValue(),
            'is_sender_read' => false
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
        } else {
            $documentSignatureForwardIds = $this->doForward($data);
            if (!$documentSignatureForwardIds) {
                throw new CustomException(
                    'Forward document failed',
                    'Return ids is missing. Please try again.'
                );
            }
            //Send notification to sender
            $this->doSendForwardNotification($data->id, $data->receiver->PeopleName);
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
     * @param  mixed  $data
     * @param  string $verifyCode
     * @return void
     */
    protected function addFooterDocument($data, $verifyCode)
    {
        try {
            $addFooter = Http::post(config('sikd.add_footer_url'), [
                'pdf' => $data->documentSignature->url,
                'qrcode' => config('sikd.url') . 'verification/document/tte/' . $verifyCode . '?source=qrcode',
                'category' => $data->documentSignature->documentSignatureType->document_paper_type,
                'code' => $verifyCode
            ]);

            return $addFooter;
        } catch (\Throwable $th) {
            throw new CustomException('Add footer document failed', $th->getMessage());
        }
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
                'body' => 'Naskah Anda telah di tandatangani oleh ' . $name . '. Klik disini untuk lihat naskah!',
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
                $receiver = People::whereHas('role', function ($role) use ($documentSignatureSent) {
                    $role->where('RoleCode', $documentSignatureSent->sender->role->RoleCode);
                    if (($documentSignatureSent->sender->PrimaryRoleId != 'uk.1' || $documentSignatureSent->sender->PrimaryRoleId != 'uk.1.1.1')) {
                        $role->where('GRoleId', $documentSignatureSent->sender->role->GRoleId);
                    }
                })->where('GroupId', PeopleGroupTypeEnum::UK()->value)->pluck('PeopleId');

                break;
            case 'TU':
                $receiver = $this->findPeopleRoleTUForwardReceiver($documentSignatureSent);
                break;

            default:
                $receiver = null;
                break;
        }

        return $receiver;
    }

    /**
     * findPeopleRoleTUForwardReceiver
     *
     * @param  object $documentSignatureSent
     * @return mixed
     */
    public function findPeopleRoleTUForwardReceiver($documentSignatureSent)
    {
        // Find people TU role with role id
        $findByRoleId = $this->queryFindPeopleRoleTU($documentSignatureSent, 'RoleId', $documentSignatureSent->sender->PrimaryRoleId);
        if (count($findByRoleId) != 0) {
            return $findByRoleId;
        }
        // If still not exist
        // Find people TU role with parent role id
        $findByParentRoleId = $this->queryFindPeopleRoleTU($documentSignatureSent, 'RoleId', $documentSignatureSent->sender->RoleAtasan);
        if (count($findByParentRoleId) != 0) {
            return $findByParentRoleId;
        }
        // If still not exist
        // Find people TU role with tiered top parent role id
        $foundTUAccount              = false;
        $removeRolePattern           = 2; // substr last string, remove number and dots from role id
        $findByTieredTopParentRoleId = null;
        do {
            // example uk.1.2.3.4.5 will be uk.1.2.3.4
            $TieredTopParentRoleId = substr($documentSignatureSent->sender->PrimaryRoleId, 0, -$removeRolePattern);

            $findByTieredTopParentRoleId = $this->queryFindPeopleRoleTU($documentSignatureSent, 'RoleId', $TieredTopParentRoleId);
            if (count($findByTieredTopParentRoleId) != 0) {
                $foundTUAccount = true;
            } else {
                $foundTUAccount = false;
                $removeRolePattern = $removeRolePattern + 2; // add 2 number for remove number and dots from role id
            }
        } while ($foundTUAccount == false);

        return $findByTieredTopParentRoleId;
    }

    /**
     * queryFindPeopleRoleTU
     *
     * @param  object $documentSignatureSent
     * @param  string $whereField
     * @param  string $whereParams
     * @return mixed
     */
    public function queryFindPeopleRoleTU($documentSignatureSent, $whereField, $whereParams)
    {
        $receiver = People::whereHas('role', function ($role) use ($documentSignatureSent, $whereField, $whereParams) {
            $role->where('RoleCode', $documentSignatureSent->sender->role->RoleCode);
            $role->where($whereField, $whereParams);
        })->where('GroupId', PeopleGroupTypeEnum::TU()->value)->pluck('PeopleId');

        return $receiver;
    }
}
