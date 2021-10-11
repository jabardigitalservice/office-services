<?php

namespace App\GraphQL\Mutations;

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
        $stringReceiversIds = Arr::get($args, 'input.receiversIds');
        $time = Carbon::now();

        $inboxData = [
            'from' => auth()->user(),
            'inboxId' => Arr::get($args, 'input.inboxId'),
            'message' => Arr::get($args, 'input.message'),
            'urgency' => Arr::get($args, 'input.urgency'),
            'receiversIds' => explode(", ", $stringReceiversIds),
            'time' => $time,
            'groupId' => auth()->user()->PeopleId . $time,
        ];

        $inboxReceivers = [];
        foreach ($inboxData['receiversIds'] as $receiverId) {
            $newInboxReceiver = $this->createInboxReceiver($inboxData, $receiverId, $action);
            array_push($inboxReceivers, $newInboxReceiver);
        }

        // If the action is disposition, should create a inboxDisposition
        if ($action == PeopleProposedTypeEnum::DISPOSITION()) {
            $this->createInboxDisposition($inboxData);
        }

        // The origin inbox's status to be marked as actioned (forwarded/dispositioned)
        $this->markActioned($inboxData);
        // Send the notification
        $this->actionNotification($inboxData);
        return $inboxReceivers;
    }

    public function endForward($rootValue, array $args)
    {
        $peopleId = auth()->user()->PeopleId;
        $inboxId = Arr::get($args, 'inboxId');

        InboxReceiver::where('NId', $inboxId)
            ->where('To_Id', strval($peopleId))
            ->firstOrFail()
            ->update(['Status' => 1]);

        return "status updated";
    }

    /**
     * @param Object $from
     * @param String $inboxId
     * @param String $message
     * @param String $receiverId
     *
     * @return InboxReceiver
     */
    protected function createInboxReceiver($inboxData, $receiverId, $action)
    {
        $receiver = People::findOrFail($receiverId);
        $nkey = TableSetting::first()->tb_key;

        $label = 'to_forward';
        if ($action == PeopleProposedTypeEnum::DISPOSITION()) {
            $label = 'cc1';
        }

        $inboxReceiver = [
            'NId' 			=> $inboxData['inboxId'],
            'NKey' 			=> $nkey,
            'GIR_Id' 		=> $inboxData['groupId'],
            'From_Id' 		=> $inboxData['from']->PeopleId,
            'RoleId_From' 	=> $inboxData['from']->PrimaryRoleId,
            'To_Id' 		=> $receiverId,
            'RoleId_To' 	=> $receiver->PrimaryRoleId,
            'ReceiverAs' 	=> $label,
            'Msg' 			=> $inboxData['message'],
            'StatusReceive' => 'unread',
            'ReceiveDate' 	=> $inboxData['time'],
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
    protected function markActioned($inboxData)
    {
        $inboxId = $inboxData['inboxId'];
        $fromId = $inboxData['from']->PeopleId;

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
    protected function actionNotification($inboxData)
    {
        $inbox = Inbox::findOrFail($inboxData['inboxId']);

        $messageAttribute = [
            'notification' => [
                'title' => $inboxData['from']->role->rolecode->rolecode_sort,
                'body' => $inbox->Hal . ' | ' . $inbox->type->JenisName . ' | ' . $inbox->urgency->UrgensiName,
            ],
            'data' => [
                'inboxId' => $inboxData['inboxId'],
                'groupId' => $inboxData['groupId'],
                'peopleIds' => $inboxData['receiversIds'],
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
    protected function createInboxDisposition($inboxData)
    {
        $inboxDisposition = [
            'NId' 		=> $inboxData['inboxId'],
            'GIR_Id' 	=> $inboxData['groupId'],
            'Sifat'     => $inboxData['urgency'],
            'RoleId' 	=> $inboxData['from']->PrimaryRoleId,
        ];

        return InboxDisposition::create($inboxDisposition);
    }
}
