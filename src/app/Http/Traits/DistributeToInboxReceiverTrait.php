<?php

namespace App\Http\Traits;

use App\Enums\FcmNotificationActionTypeEnum;
use App\Models\InboxReceiver;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Setup configuration for signature document
 */
trait DistributeToInboxReceiverTrait
{
    use SendNotificationTrait;

    /**
     * createInboxReceiver
     *
     * @param  mixed $inboxId
     * @param  mixed $args
     * @return void
     */
    public function createInboxReceiver($tableKey, $inboxId, $stringReceiversIds)
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
     * doSendNotification
     *
     * @param  mixed $inboxId
     * @param  mixed $args
     * @param  mixed $stringReceiversIds
     * @return void
     */
    protected function doSendNotification($inboxId, $args, $stringReceiversIds)
    {
        $dept = auth()->user()->role->rolecode_sort;
        $title = Arr::get($args, 'input.title');
        $body = $dept . ' telah mengirimkan surat terkait dengan ' . $title . 'Klik disini untuk membaca dan menindaklanjut pesan.';

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
