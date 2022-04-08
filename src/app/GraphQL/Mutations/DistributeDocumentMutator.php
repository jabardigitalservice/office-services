<?php

namespace App\GraphQL\Mutations;

use App\Enums\FcmNotificationActionTypeEnum;
use App\Exceptions\CustomException;
use App\Http\Traits\SendNotificationTrait;
use App\Models\DocumentSignature;
use App\Models\Inbox;
use App\Models\InboxFile;
use App\Models\InboxReceiver;
use App\Models\People;
use App\Models\TableSetting;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class DistributeDocumentMutator
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
    public function distributeDocumentToInbox($rootValue, array $args)
    {
        $letterNumber         = Arr::get($args, 'input.letterNumber');
        $documentSignatureId  = Arr::get($args, 'input.documentSignatureId');
        $inboxId              = auth()->user()->PeopleId . parseDateTimeFormat(Carbon::now(), 'dmyhis');
        $stringReceiversIds   = Arr::get($args, 'input.receiversIds');

        $checkLetterNumber = Inbox::where('Nomor', $letterNumber)->first();
        $tableKey = TableSetting::first()->tb_key;

        if ($checkLetterNumber) {
            throw new CustomException('Letter number already exists', 'Letter number already exists, please change with other number.');
        }

        $documentSignature = DocumentSignature::findOrFail($documentSignatureId);

        $response = $this->copyDocumentFilePathtoInboxFilePath($documentSignatureId);
        if ($response->status() != Response::HTTP_OK) {
            throw new CustomException('Webhook failed', json_decode($response->body()));
        } else {
            $this->createInbox($tableKey, $inboxId, $args, $letterNumber);
            $this->createInboxReceiver($tableKey, $inboxId, $stringReceiversIds);
            $this->createInboxFile($tableKey, $inboxId, $documentSignature);
            $this->doSendNotification($inboxId, $args, $stringReceiversIds);
        }

        return $documentSignature;
    }

    /**
     * createInbox
     *
     * @param  mixed $inboxId
     * @param  mixed $args
     * @param  mixed $letterNumber
     * @return void
     */
    protected function createInbox($tableKey, $inboxId, $args, $letterNumber)
    {
        $inbox = new Inbox();
        $inbox->NKey            = $tableKey;
        $inbox->NId             = $inboxId;
        $inbox->CreatedBy       = auth()->user()->PeopleId;
        $inbox->CreationRoleId  = auth()->user()->PrimaryRoleId;
        $inbox->NTglReg         = Carbon::now();
        $inbox->Tgl             = Carbon::parse(Arr::get($args, 'input.date'))->format('Y-m-d H:i:s');
        $inbox->JenisId         = Arr::get($args, 'input.documentTypeId');
        $inbox->UrgensiId       = Arr::get($args, 'input.documentUrgencyId');
        $inbox->SifatId         = Arr::get($args, 'input.classifiedId');
        $inbox->Hal             = Arr::get($args, 'input.title');
        $inbox->Nomor           = $letterNumber;
        $inbox->Pengirim        = 'eksternal';
        $inbox->NTipe           = 'outbox';
        $inbox->Namapengirim    = auth()->user()->role->RoleDesc;
        $inbox->NFileDir        = 'naskah';
        $inbox->BerkasId        = '1';
        $inbox->save();

        return $inbox;
    }

    /**
     * createInboxReceiver
     *
     * @param  mixed $inboxId
     * @param  mixed $args
     * @return void
     */
    protected function createInboxReceiver($tableKey, $inboxId, $stringReceiversIds)
    {
        $receivers  = People::whereIn('PeopleId', explode(', ', $stringReceiversIds))->get();
        foreach ($receivers as $value) {
            $InboxReceiver = new InboxReceiver();
            $InboxReceiver->NId           = $inboxId;
            $InboxReceiver->NKey          = $tableKey;
            $InboxReceiver->GIR_Id        = auth()->user()->PeopleId . Carbon::now();
            $InboxReceiver->From_Id       = auth()->user()->PeopleId;
            $InboxReceiver->RoleId_From   = auth()->user()->PrimaryRoleId;
            $InboxReceiver->To_Id         = $value->PeopleId;
            $InboxReceiver->RoleId_To     = $value->PrimaryRoleId;
            $InboxReceiver->ReceiverAs    = 'to';
            $InboxReceiver->StatusReceive = 'unread';
            $InboxReceiver->ReceiveDate   = Carbon::now();
            $InboxReceiver->To_Id_Desc    = $value->role->RoleDesc;
            $InboxReceiver->Status        = '0';
            $InboxReceiver->save();
        }

        return true;
    }

    /**
     * createInboxFile
     *
     * @param  mixed $tableKey
     * @param  mixed $inboxId
     * @param  mixed $documentSignature
     * @return void
     */
    protected function createInboxFile($tableKey, $inboxId, $documentSignature)
    {
        $inboxFile = new InboxFile();
        $inboxFile->FileKey         = $tableKey;
        $inboxFile->GIR_Id          = $inboxId;
        $inboxFile->NId             = $inboxId;
        $inboxFile->PeopleID        = auth()->user()->PeopleId;
        $inboxFile->PeopleRoleID    = auth()->user()->PrimaryRoleId;
        $inboxFile->FileName_real   = $documentSignature->file;
        $inboxFile->FileName_fake   = $documentSignature->file;
        $inboxFile->FileStatus      = 'available';
        $inboxFile->EditedDate      = Carbon::now();
        $inboxFile->Id_Dokumen      = $documentSignature->code;
        $inboxFile->save();

        return $inboxFile;
    }

    /**
     * copyDocumentFilePathtoInboxFilePath
     *
     * @param  mixed $documentSignatureId
     * @return object
     */
    protected function copyDocumentFilePathtoInboxFilePath($documentSignatureId)
    {
        $url = config('sikd.webhook_distribute_document');
        $response = Http::withHeaders([
            'Secret' => config('sikd.webhook_secret'),
        ])->asForm()->post($url, [
            'document_signature_id' => $documentSignatureId,
        ]);

        return $response;
    }

    protected function doSendNotification($inboxId, $args, $stringReceiversIds)
    {
        $dept = auth()->user()->role->rolecode_sort;
        $title = Arr::get($args, 'input.title');
        $body = $dept . ' telah mengirimkan surat terkait dengan ' . $title . 'Klik di sini untuk membaca dan menindaklanjut pesan.';

        $messageAttribute = [
            'notification' => [
                'title' => $title,
                'body' => str_replace('&nbsp;', ' ', strip_tags($body))
            ],
            'data' => [
                'inboxId' => $inboxId,
                'groupId' => $inboxId,
                'peopleIds' => explode(', ', $stringReceiversIds),
                'action' => FcmNotificationActionTypeEnum::INBOX_DETAIL(),
            ]
        ];

        $this->setupInboxReceiverNotification($messageAttribute);
    }
}
