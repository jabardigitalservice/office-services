<?php

namespace App\GraphQL\Mutations;

use App\Enums\ActionLabelTypeEnum;
use App\Enums\FcmNotificationActionTypeEnum;
use App\Enums\FcmNotificationListTypeEnum;
use App\Exceptions\CustomException;
use App\Http\Traits\SendNotificationTrait;
use App\Models\Draft;
use App\Models\InboxReceiver;
use App\Models\InboxReceiverCorrection;
use App\Models\People;
use App\Models\TableSetting;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class DraftNumberMutator
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
    public function addNumber($rootValue, array $args)
    {
        $draft = Draft::where('NId_Temp', Arr::get($args, 'input.draftId'))->first();
        if (!$draft) {
            throw new CustomException('Draft not found', 'Draft with id ' . Arr::get($args, 'input.draftId') . ' not found');
        }

        $receiver = People::where('PeopleId', Arr::get($args, 'input.receiverId'))->first();
        if (!$receiver) {
            throw new CustomException('People not found', 'People with id ' . Arr::get($args, 'input.draftId') . ' not found');
        }

        $draftNumber = Arr::get($args, 'input.number') . '/' . $draft->classification->ClCode . '/' . $draft->RoleCode;
        $checkNumber = Draft::where('nosurat', $draftNumber)
                            ->where('NId_Temp', '!=', Arr::get($args, 'input.draftId'))->first();
        if ($checkNumber) {
            throw new CustomException('Letter number already exists', 'Letter number already exists, please change with other number.');
        }

        $draft->Number  = Arr::get($args, 'input.number');
        $draft->nosurat = $draftNumber;
        $draft->save();

        $tableKey = TableSetting::first()->tb_key;
        $groupId = auth()->user()->PeopleId . Carbon::now();
        $this->createInboxReceiver($draft, $receiver, $tableKey, $groupId);
        $createInboxCorrection = $this->createInboxReceiverCorrection($draft, $receiver, $tableKey, $groupId);
        $this->doSendNotification($draft, $receiver, $createInboxCorrection->GIR_Id);

        return $createInboxCorrection;
    }

    /**
     * createInboxReceiverCorrection
     *
     * @param  mixed $draft
     * @param  mixed $receiver
     * @param  string $tableKey
     * @param  string $groupId
     * @return object
     */
    protected function createInboxReceiverCorrection($draft, $receiver, $tableKey, $groupId)
    {
        $InboxReceiverCorrection = new InboxReceiverCorrection();
        $InboxReceiverCorrection->NId           = $draft->NId_Temp;
        $InboxReceiverCorrection->NKey          = $tableKey;
        $InboxReceiverCorrection->GIR_Id        = $groupId;
        $InboxReceiverCorrection->From_Id       = auth()->user()->PeopleId;
        $InboxReceiverCorrection->RoleId_From   = auth()->user()->PrimaryRoleId;
        $InboxReceiverCorrection->To_Id         = $receiver->PeopleId;
        $InboxReceiverCorrection->RoleId_To     = $receiver->PrimaryRoleId;
        $InboxReceiverCorrection->ReceiverAs    = 'meneruskan';
        $InboxReceiverCorrection->StatusReceive = 'unread';
        $InboxReceiverCorrection->ReceiveDate   = Carbon::now();
        $InboxReceiverCorrection->To_Id_Desc    = $receiver->role->RoleDesc;
        $InboxReceiverCorrection->action_label  = ActionLabelTypeEnum::SIGNING();
        $InboxReceiverCorrection->save();

        return $InboxReceiverCorrection;
    }

    /**
     * createInboxReceiver
     *
     * @param  mixed $draft
     * @param  mixed $receiver
     * @param  string $tableKey
     * @param  string $groupId
     * @return object
     */
    protected function createInboxReceiver($draft, $receiver, $tableKey, $groupId)
    {
        $InboxReceiver = new InboxReceiver();
        $InboxReceiver->NId           = $draft->NId_Temp;
        $InboxReceiver->NKey          = $tableKey;
        $InboxReceiver->GIR_Id        = $groupId;
        $InboxReceiver->From_Id       = auth()->user()->PeopleId;
        $InboxReceiver->RoleId_From   = auth()->user()->PrimaryRoleId;
        $InboxReceiver->To_Id         = $receiver->PeopleId;
        $InboxReceiver->RoleId_To     = $receiver->PrimaryRoleId;
        $InboxReceiver->ReceiverAs    = $draft->ReceiverAs;
        $InboxReceiver->StatusReceive = 'unread';
        $InboxReceiver->ReceiveDate   = Carbon::now();
        $InboxReceiver->To_Id_Desc    = $receiver->role->RoleDesc;
        $InboxReceiver->action_label  = ActionLabelTypeEnum::REVIEW();
        $InboxReceiver->save();

        return $InboxReceiver;
    }

    /**
     * doSendNotification
     *
     * @param  mixed $draft
     * @param  mixed $receiver
     * @param  string $groupId
     * @return void
     */
    protected function doSendNotification($draft, $receiver, $groupId)
    {
        $about = str_replace('&nbsp;', ' ', strip_tags($draft->Hal));
        $body = 'Terdapat ' . $draft->type->JenisName . ' terkait dengan ' . $about . ' yang harus segera Anda tanda tangani secara digital. Klik disini untuk membaca dan menindaklanjuti pesan.';

        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => $body,
            ],
            'data' => [
                'inboxId' => $draft->NId_Temp,
                'groupId' => $groupId,
                'peopleIds' => [$receiver->PeopleId],
                'receiverAs' => 'meneruskan',
                'action' => FcmNotificationActionTypeEnum::DRAFT_DETAIL(),
                'list' => FcmNotificationListTypeEnum::DRAFT_INSIDE()
            ]
        ];

        $this->setupInboxReceiverNotification($messageAttribute);
    }
}
