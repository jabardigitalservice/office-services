<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Http\Traits\DistributeToInboxReceiverTrait;
use App\Models\Inbox;
use App\Models\TableSetting;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class DistributeInboxMutator
{
    use DistributeToInboxReceiverTrait;

     /**
     * @param $rootValue
     * @param $args
     *
     * @throws \Exception
     *
     * @return array
     */
    public function distributeInbox($rootValue, array $args)
    {
        $inboxId              = Arr::get($args, 'input.inboxId');
        $newInboxId           = auth()->user()->PeopleId . parseDateTimeFormat(Carbon::now(), 'dmyhis');
        $stringReceiversIds   = Arr::get($args, 'input.receiversIds');

        $inbox      = Inbox::where('NId', $inboxId)->first();
        if (!$inbox) {
            throw new CustomException('Inbox not found', 'Inbox with .' . $inboxId . ' not found.');
        }

        $tableKey   = TableSetting::first()->tb_key;
        $this->createInboxReceiver($tableKey, $newInboxId, $stringReceiversIds);
        $this->doSendNotification($newInboxId, $args, $stringReceiversIds);

        return $inbox;
    }
}
