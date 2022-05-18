<?php

namespace App\GraphQL\Mutations;

use App\Enums\ActionLabelTypeEnum;
use App\Enums\DraftConceptStatusTypeEnum;
use App\Enums\InboxReceiverCorrectionTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Enums\PeopleIsActiveEnum;
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
        $verifyCode = strtoupper(substr(sha1(uniqid(mt_rand(), true)), 0, 10));
        $pdfFile = $this->addFooterDocument($draft, $verifyCode);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $setupConfig['auth'],
                'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
            ])->attach('file', $pdfFile, $draft->document_file_name)->post($url, [
                'nik'           => $setupConfig['nik'],
                'passphrase'    => $passphrase,
                'tampilan'      => 'invisible',
                'image'         => 'false',
            ]);

            if ($response->status() != Response::HTTP_OK) {
                throw new CustomException('Document failed', 'Signature failed, check your file again');
            } else {
                //Save new file & update status
                $draft = $this->saveNewFile($response, $draft, $verifyCode);
            }
            //Save log
            $this->createPassphraseSessionLog($response);

            return $draft;
        } catch (\Throwable $th) {
            throw new CustomException('Connect API for sign document failed', $th->getMessage());
        }
    }

    /**
     * addFooterDocument
     *
     * @param  mixed $draft
     * @param  mixed $verifyCode
     * @return void
     */
    protected function addFooterDocument($draft, $verifyCode)
    {
        try {
            $addFooter = Http::post(config('sikd.add_footer_url'), [
                'pdf' => $draft->draft_file . '?esign=true',
                'qrcode' => config('sikd.url') . 'administrator/anri_mail_tl/log_naskah_masuk_pdf/' . $draft->NId_Temp,
                'category' => $draft->category_footer,
                'code' => $verifyCode
            ]);

            return $addFooter;
        } catch (\Throwable $th) {
            throw new CustomException('Add footer document failed', $th->getMessage());
        }
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

        try {
            //transfer to existing service
            $response = $this->doTransferFile($draft);
            if ($response->status() != Response::HTTP_OK) {
                throw new CustomException('Webhook failed', json_decode($response));
            }
            $this->doSaveSignature($draft, $verifyCode);
        } catch (\Throwable $th) {
            throw new CustomException('Connect API for webhook store file failed', $th->getMessage());
        }
        //remove temp data
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
        $response = Http::withHeaders([
            'Secret' => config('sikd.webhook_secret'),
        ])->attach('draft', $fileSignatured)->post(config('sikd.webhook_url'));

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
        $this->doUpdateInboxReceiverCorrection($draft);
        //Forward the document to TU / UK
        $this->forwardToInbox($draft);
        $draftReceiverAsToTarget = config('constants.draftReceiverAsToTarget');
        $this->forwardToInboxReceiver($draft, $draftReceiverAsToTarget);
        if (in_array($draft->ket, array_keys($draftReceiverAsToTarget))) {
            $this->forwardSaveInboxReceiverCorrection($draft, $draftReceiverAsToTarget);
        }
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
        $InboxReceiverCorrection->action_label  = ActionLabelTypeEnum::APPROVED();
        $InboxReceiverCorrection->save();

        return $InboxReceiverCorrection;
    }

    /**
     * doUpdateInboxReceiverCorrection
     *
     * @param  mixed $draft
     * @return void
     */
    protected function doUpdateInboxReceiverCorrection($draft)
    {
        $draftId = $draft->NId_Temp;
        $userRoleId = auth()->user()->PrimaryRoleId;
        InboxReceiverCorrection::where('NId', $draftId)
            ->where('RoleId_To', $userRoleId)
            ->update(['action_label' => ActionLabelTypeEnum::SIGNED()]);
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

    protected function forwardToInboxReceiver($draft, $draftReceiverAsToTarget)
    {
        $receiver = $this->getTargetInboxReceiver($draft, $draftReceiverAsToTarget);
        $labelReceiverAs = (in_array($draft->ket, array_keys($draftReceiverAsToTarget))) ? $draftReceiverAsToTarget[$draft->Ket] : 'to_forward';
        $groupId = auth()->user()->PeopleId . Carbon::now();
        $this->doForwardToInboxReceiver($draft, $receiver, $labelReceiverAs, $groupId);

        if ($draft->RoleId_Cc != null && in_array($draft->ket, array_keys($draftReceiverAsToTarget))) {
            $peopleCCIds = People::whereIn('PrimaryRoleId', explode(',', $draft->RoleId_Cc))
                            ->where('PeopleIsActive', PeopleIsActiveEnum::ACTIVE()->value)
                            ->get();
            $this->doForwardToInboxReceiver($draft, $peopleCCIds, 'bcc', $groupId);
        }

        return $receiver;
    }

    /**
     * doForwardToInboxReceiver
     *
     * @param  mixed $draft
     * @param  mixed $receiver
     * @param  string $receiverAs
     * @param  string $groupId
     * @return void
     */
    protected function doForwardToInboxReceiver($draft, $receiver, $receiverAs, $groupId)
    {
        foreach ($receiver as $key => $value) {
            $InboxReceiver = new InboxReceiver();
            $InboxReceiver->NId           = $draft->NId_Temp;
            $InboxReceiver->NKey          = TableSetting::first()->tb_key;
            $InboxReceiver->GIR_Id        = $groupId;
            $InboxReceiver->From_Id       = auth()->user()->PeopleId;
            $InboxReceiver->RoleId_From   = auth()->user()->PrimaryRoleId;
            $InboxReceiver->To_Id         = $value->PeopleId;
            $InboxReceiver->RoleId_To     = $value->PrimaryRoleId;
            $InboxReceiver->ReceiverAs    = $receiverAs;
            $InboxReceiver->StatusReceive = 'unread';
            $InboxReceiver->ReceiveDate   = Carbon::now();
            $InboxReceiver->To_Id_Desc    = $value->role->RoleDesc;
            $InboxReceiver->Status        = '0';
            $InboxReceiver->action_label  = ActionLabelTypeEnum::REVIEW();
            $InboxReceiver->save();
        }

        return true;
    }

    /**
     * getTargetInboxReceiver
     *
     * @param  mixed $draft
     * @param  array $draftReceiverAsToTarget
     * @return array
     */

    protected function getTargetInboxReceiver($draft, $draftReceiverAsToTarget)
    {
        if (in_array($draft->ket, array_keys($draftReceiverAsToTarget))) {
            $peopleIds = People::whereIn('PeopleId', explode(',', $draft->RoleId_To))
                        ->where('PeopleIsActive', PeopleIsActiveEnum::ACTIVE()->value)
                        ->get();
        } else {
            $peopleIds = People::whereHas('role', function ($role) {
                $role->where('RoleCode', auth()->user()->role->RoleCode);
                $role->where('GRoleId', auth()->user()->role->GRoleId);
            })->where('GroupId', PeopleGroupTypeEnum::UK()->value)
            ->where('PeopleIsActive', PeopleIsActiveEnum::ACTIVE()->value)
            ->get();
        }

        return $peopleIds;
    }

    /**
     * forwardSaveInboxReceiverCorrection
     *
     * @param  mixed $draft
     * @param  array $draftReceiverAsToTarget
     * @return void
     */
    protected function forwardSaveInboxReceiverCorrection($draft, $draftReceiverAsToTarget)
    {
        $receiver = $this->getTargetInboxReceiver($draft, $draftReceiverAsToTarget);
        foreach ($receiver as $key => $value) {
            $InboxReceiverCorrection = new InboxReceiverCorrection();
            $InboxReceiverCorrection->NId           = $draft->NId_Temp;
            $InboxReceiverCorrection->NKey          = TableSetting::first()->tb_key;
            $InboxReceiverCorrection->GIR_Id        = auth()->user()->PeopleId . Carbon::now()->addSeconds(1);
            $InboxReceiverCorrection->From_Id       = auth()->user()->PeopleId;
            $InboxReceiverCorrection->RoleId_From   = auth()->user()->PrimaryRoleId;
            $InboxReceiverCorrection->To_Id         = $value->PeopleId;
            $InboxReceiverCorrection->RoleId_To     = $value->PrimaryRoleId;
            $InboxReceiverCorrection->ReceiverAs    = 'meneruskan';
            $InboxReceiverCorrection->StatusReceive = 'unread';
            $InboxReceiverCorrection->ReceiveDate   = Carbon::now()->addSeconds(1);
            $InboxReceiverCorrection->To_Id_Desc    = $value->role->RoleDesc;
            $InboxReceiverCorrection->action_label  = ActionLabelTypeEnum::REVIEW();
            $InboxReceiverCorrection->save();
        }
        return $InboxReceiverCorrection;
    }
}
