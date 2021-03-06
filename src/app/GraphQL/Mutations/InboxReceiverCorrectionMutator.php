<?php

namespace App\GraphQL\Mutations;

use App\Enums\ActionLabelTypeEnum;
use App\Enums\FcmNotificationActionTypeEnum;
use App\Enums\FcmNotificationListTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Models\Draft;
use App\Models\InboxCorrection;
use App\Models\InboxReceiverCorrection;
use App\Models\People;
use App\Models\TableSetting;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class InboxReceiverCorrectionMutator
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
    public function backToDrafter($rootValue, array $args)
    {
        $inbox = InboxReceiverCorrection::findOrFail(Arr::get($args, 'input.id'));
        $time = Carbon::now();

        $draftData = [
            'sender'        => auth()->user(),
            'draftId'       => $inbox->NId,
            'message'       => Arr::get($args, 'input.message'),
            'receiversIds'  => explode(", ", Arr::get($args, 'input.drafterId')),
            'groupId'       => auth()->user()->PeopleId . $time,
            'options'       => Arr::get($args, 'input.options'),
            'time'          => $time
        ];

        $this->createNewInboxCorrection($draftData);
        $this->updateInboxStatus($inbox);

        $drafter = People::findOrFail(Arr::get($args, 'input.drafterId'));
        $newInbox = $this->createNewInbox($draftData, $drafter);
        $this->updateOriginDraft($inbox, $drafter);
        $this->actionNotification($draftData);
        return $newInbox;
    }

    /**
     * updateInboxStatus
     * @param Array $draftData
     *
     * @throws \Exception
     *
     * @return Void
     */
    protected function updateInboxStatus($inbox)
    {
        $inbox->Status = 1;
        $inbox->action_label = ActionLabelTypeEnum::REVIEWED();
        $inbox->save();
    }

    /**
     * Create new inbox receiver correction record
     * @param Array $draftData
     * @param People $drafter
     *
     * @throws \Exception
     *
     * @return InboxReceiverCorrection
     */
    protected function createNewInbox($draftData, $drafter)
    {
        $inbox                  = new InboxReceiverCorrection();
        $inbox->NId             = $draftData['draftId'];
        $inbox->NKey            = TableSetting::first()->tb_key;
        $inbox->GIR_Id          = $draftData['groupId'];
        $inbox->From_Id         = $draftData['sender']->PeopleId;
        $inbox->RoleId_From     = $draftData['sender']->PrimaryRoleId;
        $inbox->To_Id           = $drafter->PeopleId;
        $inbox->RoleId_To       = $drafter->PrimaryRoleId;
        $inbox->ReceiverAs      = 'koreksi';
        $inbox->Msg             = $draftData['message'];
        $inbox->StatusReceive   = 'unread';
        $inbox->ReceiveDate     = $draftData['time'];
        $inbox->To_Id_Desc      = $drafter->role->RoleName;
        $inbox->action_label    = ActionLabelTypeEnum::CORRECTION();
        $inbox->save();

        return $inbox;
    }

    /**
     * Create inbox correction record
     * @param Array $draftData
     *
     * @throws \Exception
     *
     * @return Void
     */
    protected function createNewInboxCorrection($draftData)
    {
        $inbox          = new InboxCorrection();
        $inbox->NId     = $draftData['draftId'];
        $inbox->GIR_Id  = $draftData['groupId'];
        $inbox->Koreksi = $draftData['options'];
        $inbox->RoleId  = $draftData['sender']->PrimaryRoleId;
        $inbox->save();
    }

    /**
     * Update the origin draft
     *
     * @param InboxReceiverCorrection $inbox
     * @param People                  $drafter
     *
     * @throws \Exception
     *
     * @return Void
     */
    private function updateOriginDraft($inbox, $drafter)
    {
        $draft = Draft::where('NId_Temp', $inbox->NId)->firstOrFail();
        $draft->Approve_People = $drafter->PeopleId;
        $draft->Nama_ttd_konsep = $drafter->PeopleName;
        $draft->save();
    }

    /**
     * Send notification
     * @param Array $draftData
     * @param String $action
     *
     * @return void
     */
    protected function actionNotification($draftData)
    {
        $draft = Draft::findOrFail($draftData['draftId']);
        $about = str_replace('&nbsp;', ' ', strip_tags($draft->Hal));
        $peopleId = substr($draftData['groupId'], 0, -19);
        $dateString = substr($draftData['groupId'], -19);
        $date = parseDateTimeFormat($dateString, 'dmyhis');
        $title = 'Perbaikan Naskah';
        $body = 'Terdapat ' . $draft->type->JenisName . ' terkait dengan ' . $about . ' yang perlu diperbaiki terlebih dahulu. Klik di sini untuk informasi lebih lanjut.';

        $messageAttribute = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'inboxId' => $draftData['draftId'],
                'groupId' => $peopleId . $date,
                'peopleIds' => $draftData['receiversIds'],
                'receiverAs' => 'koreksi',
                'action' => FcmNotificationActionTypeEnum::DRAFT_REVIEW(),
                'list' => FcmNotificationListTypeEnum::DRAFT_INSIDE()
            ]
        ];

        $this->setupInboxReceiverNotification($messageAttribute);
    }
}
