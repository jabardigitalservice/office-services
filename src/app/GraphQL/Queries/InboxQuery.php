<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\InboxReceiver;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InboxQuery
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
        $inboxReceiver = InboxReceiver::where('NId', $args['NId'])
                            ->where('To_Id', $context->user()->PeopleId)
                            ->first();

        if ($inboxReceiver) {
            // @TODO Seharusnya mark as read dipisahkan dari proses/logic ini (single responsibility principle)
            if ($inboxReceiver->StatusReceive != 'read') {
                InboxReceiver::where('NId', $inboxReceiver->NId)
                            ->where('To_Id', $context->user()->PeopleId)
                            ->update(['StatusReceive' => 'read']);
            }

            return $inboxReceiver;
        } else {
            throw new CustomException(
                'Inbox not found',
                'Inbox with this NId not found'
            );
        }
    }
}
