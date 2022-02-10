<?php

namespace App\GraphQL\Mutations;

use App\Enums\DraftConceptStatusTypeEnum;
use App\Enums\InboxReceiverCorrectionTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Exceptions\CustomException;
use App\Http\Traits\DraftTrait;
use App\Http\Traits\SignatureTrait;
use App\Models\Draft;
use App\Models\Inbox;
use App\Models\InboxFile;
use App\Models\InboxReceiver;
use App\Models\InboxReceiverCorrection;
use App\Models\People;
use App\Models\Signature;
use App\Models\TableSetting;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
        $draftId    = Arr::get($args, 'input.draftId');
        $passphrase = Arr::get($args, 'input.passphrase');
        $draftHistory = InboxReceiverCorrection::where('NId', $draftId)
                        ->where('ReceiverAs', InboxReceiverCorrectionTypeEnum::SIGNED()->value)->first();

        if ($draftHistory) {
            throw new CustomException('Document already signed', 'Status of this document is already signed');
        }

        $setupConfig = $this->setupConfigSignature();
        $checkUser = json_decode($this->checkUserSignature($setupConfig));
        if ($checkUser->status_code != 1111) {
            throw new CustomException('Invalid user', 'Invalid credential user, please check your passphrase again');
        }
        $draft     = Draft::where('NId_temp', $draftId)->first();
        $signature = $this->doSignature($setupConfig, $draft, $passphrase);

        $draft->Konsep = DraftConceptStatusTypeEnum::SENT()->value;
        $draft->save();

        return $signature;
    }

    /**
     * doSignature
     *
     * @param  array $setupConfig
     * @param  collection $draft
     * @param  string $passphrase
     * @return collection
     */
    protected function doSignature($setupConfig, $draft, $passphrase)
    {
        $url = $setupConfig['url'] . '/api/sign/pdf';
        $verifyCode = substr(sha1(uniqid(mt_rand(), true)), 0, 10);
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $setupConfig['auth'],
            'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
        ])->attach('file', $this->setDraftDocumentPdf($draft->NId_Temp, $verifyCode), $draft->document_file_name)->post($url, [
            'nik'           => $setupConfig['nik'],
            'passphrase'    => $passphrase,
            'tampilan'      => 'invisible',
            'page'          => '1',
            'image'         => 'false',
        ]);

        if ($response->status() != Response::HTTP_OK) {
            throw new CustomException('Document failed', 'Signature failed, check your file again');
        } else {
            //Save new file & update status
            $draft = $this->saveNewFile($response, $draft, $verifyCode);
            //Save log
            $this->createPassphraseSessionLog($response);
        }

        return $draft;
    }

    /**
     * saveNewFile
     *
     * @param  mixed $pdf
     * @param  collection $draft
     * @param  string $verifyCode
     * @return collection
     */
    protected function saveNewFile($pdf, $draft, $verifyCode)
    {
        //save signed data
        Storage::disk('local')->put($draft->document_file_name, $pdf->body());
        //transfer to existing service
        $response = $this->doTransferFile($draft);
        if ($response->status() != Response::HTTP_OK) {
            throw new CustomException('Webhook failed', json_decode($response));
        }
        $this->doSaveSignature($draft, $verifyCode);
        //remove temp data
        Storage::disk('local')->delete($draft->NId_Temp . '.png');
        Storage::disk('local')->delete($draft->document_file_name);

        return $draft;
    }

    /**
     * doTransferFile
     *
     * @param  collection $draft
     * @return mixed
     */
    public function doTransferFile($draft)
    {
        $fileSignatured = fopen(Storage::path($draft->document_file_name), 'r');
        $QrCode = fopen(Storage::path($draft->NId_Temp . '.png'), 'r');
        $response = Http::withHeaders([
            'Secret' => config('sikd.webhook_secret'),
        ])->attach('draft', $fileSignatured)->attach('qrcode', $QrCode)->post(config('sikd.webhook_url'));

        return $response;
    }

    /**
     * doSaveSignature
     *
     * @param  mixed $draft
     * @param  mixed $verifyCode
     * @return mixed
     */
    protected function doSaveSignature($draft, $verifyCode)
    {
        $signature = new Signature();
        $signature->NId    = $draft->NId_Temp;
        $signature->TglProses   = Carbon::now();
        $signature->PeopleId    = auth()->user()->PeopleId;
        $signature->RoleId      = auth()->user()->PrimaryRoleId;
        $signature->Verifikasi  = $verifyCode;
        $signature->QRCode      = $draft->NId_Temp . '.png';
        $signature->save();

        $this->doSaveInboxFile($draft, $verifyCode);
        $this->doUpdateInboxReceiver($draft);
        $this->doSaveInboxReceiverCorrection($draft);
        //Forward the document to TU / UK
        $this->forwardToInbox($draft);
        $this->forwardToInboxReceiver($draft);

        return $signature;
    }

    /**
     * doSaveInboxFile
     *
     * @param  mixed $draft
     * @param  mixed $verifyCode
     * @return void
     */
    protected function doSaveInboxFile($draft, $verifyCode)
    {
        $inboxFile = new InboxFile();
        $inboxFile->FileKey         = TableSetting::first()->tb_key;
        $inboxFile->GIR_Id          = $draft->GIR_Id;
        $inboxFile->NId             = $draft->NId_Temp;
        $inboxFile->PeopleID        = auth()->user()->PeopleId;
        $inboxFile->PeopleRoleID    = auth()->user()->PrimaryRoleId;
        $inboxFile->FileName_real   = $draft->document_file_name;
        $inboxFile->FileName_fake   = $draft->document_file_name;
        $inboxFile->FileStatus      = 'available';
        $inboxFile->EditedDate      = Carbon::now();
        $inboxFile->Keterangan      = 'outbox';
        $inboxFile->Id_Dokumen      = $verifyCode;
        $inboxFile->save();

        return $inboxFile;
    }

    /**
     * doUpdateInboxReceiver
     *
     * @param  mixed $draft
     * @return void
     */
    protected function doUpdateInboxReceiver($draft)
    {
        $InboxReceiver = InboxReceiver::where('NId', $draft->NId_Temp)
                                        ->where('RoleId_To', auth()->user()->RoleId)
                                        ->update(['Status' => 1,'StatusReceive' => 'read']);
        return $InboxReceiver;
    }

    /**
     * doSaveInboxReceiverCorrection
     *
     * @param  mixed $draft
     * @return void
     */
    protected function doSaveInboxReceiverCorrection($draft)
    {
        $InboxReceiverCorrection = new InboxReceiverCorrection();
        $InboxReceiverCorrection->NId           = $draft->NId_Temp;
        $InboxReceiverCorrection->NKey          = TableSetting::first()->tb_key;
        $InboxReceiverCorrection->GIR_Id        = auth()->user()->PeopleId . Carbon::now();
        $InboxReceiverCorrection->From_Id       = auth()->user()->PeopleId;
        $InboxReceiverCorrection->RoleId_From   = auth()->user()->PrimaryRoleId;
        $InboxReceiverCorrection->To_Id         = ($draft->TtdText == 'none') ? auth()->user()->PeopleId : null;
        $InboxReceiverCorrection->RoleId_To     = ($draft->TtdText == 'none') ? auth()->user()->PrimaryRoleId : null;
        $InboxReceiverCorrection->ReceiverAs    = 'approvenaskah';
        $InboxReceiverCorrection->StatusReceive = 'unread';
        $InboxReceiverCorrection->ReceiveDate   = Carbon::now();
        $InboxReceiverCorrection->To_Id_Desc    = ($draft->TtdText == 'none') ? auth()->user()->RoleDesc : null;
        $InboxReceiverCorrection->save();

        return $InboxReceiverCorrection;
    }

    /**
     * forwardToInbox
     *
     * @param  mixed $draft
     * @return void
     */
    protected function forwardToInbox($draft)
    {
        $inbox = new Inbox();
        $inbox->NKey            = TableSetting::first()->tb_key;
        $inbox->NId             = $draft->NId_Temp;
        $inbox->CreatedBy       = auth()->user()->PeopleId;
        $inbox->CreationRoleId  = auth()->user()->PrimaryRoleId;
        $inbox->NTglReg         = Carbon::now();
        $inbox->Tgl             = Carbon::parse($draft->TglNaskah)->format('Y-m-d H:i:s');
        $inbox->JenisId         = $draft->JenisId;
        $inbox->UrgensiId       = $draft->UrgensiId;
        $inbox->SifatId         = $draft->SifatId;
        $inbox->Nomor           = $draft->nosurat;
        $inbox->Hal             = $draft->Hal;
        $inbox->Pengirim        = 'internal';
        $inbox->NTipe           = $draft->Ket;
        $inbox->Namapengirim    = auth()->user()->role->RoleDesc;
        $inbox->NFileDir        = 'naskah';
        $inbox->BerkasId        = '1';
        $inbox->save();

        return $inbox;
    }

    /**
     * forwardToInboxReceiver
     *
     * @param  mixed $draft
     * @return void
     */

    protected function forwardToInboxReceiver($draft)
    {
        $receiver = $this->getTargetInboxReceiver($draft);

        foreach ($receiver as $key => $value) {
            $InboxReceiver = new InboxReceiver();
            $InboxReceiver->NId           = $draft->NId_Temp;
            $InboxReceiver->NKey          = TableSetting::first()->tb_key;
            $InboxReceiver->GIR_Id        = $draft->CreatedBy . Carbon::now();
            $InboxReceiver->From_Id       = auth()->user()->PeopleId;
            $InboxReceiver->RoleId_From   = auth()->user()->PrimaryRoleId;
            $InboxReceiver->To_Id         = $value->PeopleId;
            $InboxReceiver->RoleId_To     = $value->PrimaryRoleId;
            $InboxReceiver->ReceiverAs    = 'to_forward';
            $InboxReceiver->StatusReceive = 'unread';
            $InboxReceiver->ReceiveDate   = Carbon::now();
            $InboxReceiver->To_Id_Desc    = auth()->user()->role->RoleDesc;
            $InboxReceiver->Status        = '0';
            $InboxReceiver->save();
        }

        return $receiver;
    }

    /**
     * getTargetInboxReceiver
     *
     * @param  mixed $draft
     * @return array
     */

    protected function getTargetInboxReceiver($draft)
    {
        if ($draft->Ket === 'outboxnotadinas') {
            // After signed draft, the document with 'nota dinas' will be forwarded to Receiver & CC People Ids
            $peopleToIds = People::whereIn('PeopleId', explode(',', $draft->RoleId_To))->get();
            $peopleCCIds = People::whereIn('PeopleId', explode(',', $draft->RoleId_Cc))->get();
            $peopleIds = $peopleToIds->merge($peopleCCIds);
        } else {
            $peopleIds = People::whereHas('role', function ($role) {
                $role->where('RoleCode', auth()->user()->role->RoleCode);
                $role->where('GRoleId', auth()->user()->role->GRoleId);
            })->where('GroupId', PeopleGroupTypeEnum::UK()->value)->get();
        }

        return $peopleIds;
    }
}
