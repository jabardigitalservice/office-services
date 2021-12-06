<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\InboxReceiverCorrection;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DraftQuery
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
    public function detail($rootValue, array $args, GraphQLContext $context)
    {
        $inboxReceiverCorrection = InboxReceiverCorrection::where('NId', $args['draftId'])
                                                        ->where('GIR_Id', $args['groupId'])
                                                        ->first();

        if (!$inboxReceiverCorrection) {
            throw new CustomException(
                'Draft not found',
                'Draft with this NId not found'
            );
        }

        $inboxReceiverCorrection->StatusReceive = 'read';
        $inboxReceiverCorrection->save();

        return $inboxReceiverCorrection;
    }

    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @throws \Exception
     *
     * @return array
     */
    public function timeline($rootValue, array $args, GraphQLContext $context)
    {
        $draftId = Arr::get($args, 'filter.draftId');
        $type = Arr::get($args, 'filter.type');

        $firstDraft = InboxReceiverCorrection::where('NId', $draftId)
                                            ->where('ReceiverAs', 'LIKE', '%to_draft_%')
                                            ->pluck('id');

        $reviewDraft = InboxReceiverCorrection::where('NId', $draftId)
                                            ->where('ReceiverAs', $type)
                                            ->get()
                                            ->unique('To_Id')
                                            ->pluck('id');

        $inboxReceiverCorrections = InboxReceiverCorrection::whereIn('id', $reviewDraft->merge($firstDraft))
                                                            ->orderBy('ReceiveDate', 'desc')
                                                            ->get();

        return $inboxReceiverCorrections;
    }
}
