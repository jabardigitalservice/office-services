<?php

namespace App\GraphQL\Mutations;

use App\Http\Traits\SendNotificationTrait;
use App\Models\Inbox;
use App\Models\InboxReceiver;
use App\Models\People;
use App\Models\TableSetting;
use Illuminate\Support\Arr;

class InboxMutator
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
    public function forward($rootValue, array $args)
    {
        $from = auth()->user();
        $inboxId = Arr::get($args, 'input.inboxId');
        $message = Arr::get($args, 'input.message');
        $stringReceiversIds = Arr::get($args, 'input.receiversIds');
        $receiversIds = explode(", ", $stringReceiversIds);

        $inboxReceivers = [];
        foreach ($receiversIds as $receiverId) {
            $newInboxReceiver = $this->createInboxReceiver($from, $inboxId, $message, $receiverId);
            array_push($inboxReceivers, $newInboxReceiver);
        }

        // The origin inbox's status to be marked as forwarded
        $this->markActioned($inboxId, $from->PeopleId);
        // Send the notification
        $this->actionNotification($from, $inboxId, $receiversIds);
        return $inboxReceivers;
    }

    /**
     * @param Object $from
     * @param String $inboxId
     * @param String $message
     * @param String $receiverId
     *
     * @return InboxReceiver
     */
    protected function createInboxReceiver($from, $inboxId, $message, $receiverId)
    {
        $receiver = People::findOrFail($receiverId);
        $nkey = TableSetting::first()->tb_key;
        $receiveDate = date('Y-m-d H:i:s');

        $inboxReceiver = [
            'NId' 			=> $inboxId,
            'NKey' 			=> $nkey,
            'GIR_Id' 		=> $from->PeopleId . date('dmyhis'),
            'From_Id' 		=> $from->PeopleId,
            'RoleId_From' 	=> $from->PrimaryRoleId,
            'To_Id' 		=> $receiverId,
            'RoleId_To' 	=> $receiver->PrimaryRoleId,
            'ReceiverAs' 	=> 'to_forward',
            'Msg' 			=> $message,
            'StatusReceive' => 'unread',
            'ReceiveDate' 	=> $receiveDate,
            'To_Id_Desc' 	=> $receiver->role->RoleDesc,
            'Status' 	    => 0,
        ];

        return InboxReceiver::create($inboxReceiver);
    }

    /**
     * @param String    $inboxId
     * @param Int       $fromId
     *
     * @return void
     */
    protected function markActioned($inboxId, $fromId)
    {
        $inbox = InboxReceiver::where('NId', $inboxId)
            ->where('To_Id', strval($fromId))
            ->firstOrFail();

        if ($inbox->Status != 1) {
            InboxReceiver::where('NId', $inboxId)
                ->where('To_Id', strval($fromId))
                ->update(['Status' => 1]);
        }
    }

    /**
     * @param String    $inboxId
     * @param Int       $fromId
     *
     * @return void
     */
    protected function actionNotification($from, $inboxId, $receiversIds)
    {
        $inbox = Inbox::findOrFail($inboxId);

        $request = [
            'inboxId' 		=> $inboxId,
            'date' 	        => $inbox->NTglReg,
            'about' 		=> $inbox->Hal,
            'sender' 		=> $from->role->rolecode->rolecode_sort,
            'source' 		=> $inbox->Pengirim,
            'typeName' 		=> $inbox->type->JenisName,
            'urgencyName' 	=> $inbox->urgency->UrgensiName,
            'peopleIds' 	=> $receiversIds,
        ];

        $this->sendNotification((object) $request);
    }
}
