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
                                                        ->orderBy('id', 'DESC')
                                                        ->get();

        if (!$inboxReceiverCorrection) {
            throw new CustomException(
                'Draft not found',
                'Draft with this user not found'
            );
        }

        $draft = Draft::where('NId_Temp', $args['draftId'])->first();
        $inboxReceiver = null;
        if ($draft->Ket === 'outboxnotadinas') {
            $inboxReceiver = InboxReceiver::where('NId', $args['draftId'])
                                    ->where('ReceiverAs', 'LIKE', '%to%')
                                    ->where('ReceiverAS', 'NOT LIKE', 'to_draft%')
                                    ->orderBy('id', 'DESC')
                                    ->get();
        }

        $data = collect([
            'inboxReceiverCorrection' => $inboxReceiverCorrection,
            'inboxReceiver' => $inboxReceiver
        ]);

        return $data;
    }
}
