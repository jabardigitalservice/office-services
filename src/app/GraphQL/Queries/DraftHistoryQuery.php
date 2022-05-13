<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\Draft;
use App\Models\InboxReceiver;
use App\Models\InboxReceiverCorrection;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DraftHistoryQuery
{
    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @throws \Exception
     *
     * @return array
     */
    public function history($rootValue, array $args, GraphQLContext $context)
    {
        $inboxReceiverCorrection = InboxReceiverCorrection::where('NId', $args['draftId'])
                                                        ->where('ReceiverAs', '!=', 'to_koreksi')
                                                        ->where('ReceiverAs', '!=', 'to')
                                                        ->with(['sender', 'receiver'])
                                                        ->orderBy('id', 'DESC')
                                                        ->get();

        if (!$inboxReceiverCorrection) {
            throw new CustomException(
                'Draft not found',
                'Draft with this user not found'
            );
        }

        $inboxReceiver = null;
        $draft = Draft::where('NId_Temp', $args['draftId'])->first();
        $inboxReceiver = InboxReceiver::with(['sender', 'receiver'])
                                ->orWhere(function ($query) use ($args, $draft) {
                                    $query->where('NId', $args['draftId']);
                                    $query->where('ReceiverAs', 'LIKE', '%to%');
                                    $query->where('ReceiverAs', 'NOT LIKE', 'to_draft%');
                                    if ($draft->Ket != 'outboxnotadinas') {
                                        $query->where('ReceiverAs', '<>', 'to_forward');
                                    }
                                })
                                ->orWhere(function ($query) use ($args, $draft) {
                                    $query->where('NId', $args['draftId']);
                                    $query->where('ReceiverAs', 'bcc');
                                    $query->where('ReceiverAs', 'NOT LIKE', 'to_draft%');
                                    if ($draft->Ket != 'outboxnotadinas') {
                                        $query->where('ReceiverAs', '<>', 'to_forward');
                                    }
                                })
                                ->orderBy('id', 'DESC')
                                ->get();

        $data = collect([
            'inboxReceiverCorrection' => $inboxReceiverCorrection,
            'inboxReceiver' => $inboxReceiver
        ]);

        return $data;
    }
}
