<?php

namespace App\GraphQL\Mutations;

use App\Enums\FcmNotificationActionTypeEnum;
use App\Enums\PeopleProposedTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Models\Inbox;
use App\Models\InboxDisposition;
use App\Models\InboxReceiver;
use App\Models\People;
use App\Models\TableSetting;
use Illuminate\Support\Arr;
use Carbon\Carbon;

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
        // Forward is the default action
        $action = Arr::get($args, 'input.action') ?? PeopleProposedTypeEnum::FORWARD();

        $from = auth()->user();
        $inboxId = Arr::get($args, 'input.inboxId');
        $message = Arr::get($args, 'input.message');
        $stringReceiversIds = Arr::get($args, 'input.receiversIds');
        $urgency = Arr::get($args, 'input.urgency');
        $receiversIds = explode(", ", $stringReceiversIds);
        $time = Carbon::now();
        $groupId = $from->PeopleId . $time;

        $inboxReceivers = [];
        foreach ($receiversIds as $receiverId) {
            $newInboxReceiver = $this->createInboxReceiver($from, $inboxId, $groupId, $time, $message, $receiverId, $action);
            array_push($inboxReceivers, $newInboxReceiver);
        }

        // If the action is disposition, should create a inboxDisposition
        if ($action == PeopleProposedTypeEnum::DISPOSITION()) {
            $this->createInboxDisposition($from, $inboxId, $groupId, $urgency);
        }

        // The origin inbox's status to be marked as actioned (forwarded/dispositioned)
        $this->markActioned($inboxId, $from->PeopleId);
        // Send the notification
        $this->actionNotification($from, $inboxId, $groupId, $receiversIds);
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
    protected function createInboxReceiver($from, $inboxId, $groupId, $time, $message, $receiverId, $action)
    {
        $receiver = People::findOrFail($receiverId);
        $nkey = TableSetting::first()->tb_key;
        $label = 'to_forward';

        if ($action == PeopleProposedTypeEnum::DISPOSITION()) {
            $label = 'cc1';
        }

        $inboxReceiver = [
            'NId' 			=> $inboxId,
            'NKey' 			=> $nkey,
            'GIR_Id' 		=> $groupId,
            'From_Id' 		=> $from->PeopleId,
            'RoleId_From' 	=> $from->PrimaryRoleId,
            'To_Id' 		=> $receiverId,
            'RoleId_To' 	=> $receiver->PrimaryRoleId,
            'ReceiverAs' 	=> $label,
            'Msg' 			=> $message,
            'StatusReceive' => 'unread',
            'ReceiveDate' 	=> $time,
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
    protected function actionNotification($from, $inboxId, $groupId, $receiversIds)
    {
        $inbox = Inbox::findOrFail($inboxId);

        $messageAttribute = [
            'notification' => [
                'title' => $from->role->rolecode->rolecode_sort,
                'body' => $inbox->Hal . ' | ' . $inbox->type->JenisName . ' | ' . $inbox->urgency->UrgensiName,
            ],
            'data' => [
                'inboxId' => $inboxId,
                'groupId' => $groupId,
                'peopleIds' => $receiversIds,
            ]
        ];

        $this->setupInboxReceiverNotification($messageAttribute);
    }

    /**
     * @param Object $from
     * @param String $inboxId
     * @param String $urgency
     *
     * @return InboxDisposition
     */
    protected function createInboxDisposition($from, $inboxId, $groupId, $urgency)
    {
        $inboxDisposition = [
            'NId' 		=> $inboxId,
            'GIR_Id' 	=> $groupId,
            'Sifat'     => $urgency,
            'RoleId' 	=> $from->PrimaryRoleId,
        ];

        return InboxDisposition::create($inboxDisposition);
    }
}
