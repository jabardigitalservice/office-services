<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\InboxReceiverCorrection;
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
        $inboxReceiverCorrection->StatusReceive = 'read';
        $inboxReceiverCorrection->save();

        if (!$inboxReceiverCorrection) {
            throw new CustomException(
                'Draft not found',
                'Draft with this NId not found'
            );
        }

        return $inboxReceiverCorrection;
    }
}