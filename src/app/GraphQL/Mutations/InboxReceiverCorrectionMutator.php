<?php

namespace App\GraphQL\Mutations;

use App\Models\InboxCorrection;
use App\Models\InboxReceiverCorrection;
use App\Models\People;
use App\Models\TableSetting;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class InboxReceiverCorrectionMutator
{
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
        $time = Carbon::now();
        $inbox = InboxReceiverCorrection::findOrFail(Arr::get($args, 'input.id'));
        $this->createNewInboxCorrection($inbox, $args, $time);
        $this->updateInboxStatus($inbox);
        $newInbox = $this->createNewInbox($inbox, $args, $time);
        return $newInbox;
    }

    /**
     * updateInboxStatus
     * @param InboxReceiverCorrection $inbox
     *
     * @throws \Exception
     *
     * @return Void
     */
    protected function updateInboxStatus($inbox)
    {
        $inbox->Status = 1;
        $inbox->save();
    }

    /**
     * Create new inbox receiver correction record
     * @param InboxReceiverCorrection $origin
     * @param $args
     * @param Object $time
     *
     * @throws \Exception
     *
     * @return InboxReceiverCorrection
     */
    protected function createNewInbox($origin, $args, $time)
    {
        $inbox                  = new InboxReceiverCorrection();
        $inbox->NId             = $origin->NId;
        $inbox->NKey            = TableSetting::first()->tb_key;
        $inbox->GIR_Id          = auth()->user()->PeopleId . $time;
        $inbox->From_Id         = auth()->user()->PeopleId;
        $inbox->RoleId_From     = auth()->user()->PrimaryRoleId;
        $inbox->To_Id           = $origin->From_Id;
        $inbox->RoleId_To       = $origin->RoleId_From;
        $inbox->ReceiverAs      = 'koreksi';
        $inbox->Msg             = Arr::get($args, 'input.message');
        $inbox->StatusReceive   = 'unread';
        $inbox->ReceiveDate     = $time;
        $inbox->To_Id_Desc      = People::findOrFail($origin->From_Id)->role->RoleName;
        $inbox->save();

        return $inbox;
    }

    /**
     * Create inbox correction record
     * @param InboxReceiverCorrection $origin
     * @param $args
     * @param Object $time
     *
     * @throws \Exception
     *
     * @return Void
     */
    protected function createNewInboxCorrection($origin, $args, $time)
    {
        $inbox          = new InboxCorrection();
        $inbox->NId     = $origin->NId;
        $inbox->GIR_Id  = auth()->user()->PeopleId . $time;
        $inbox->Koreksi = Arr::get($args, 'input.options');
        $inbox->RoleId  = auth()->user()->PrimaryRoleId;
        $inbox->save();
    }
}
