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
        $inboxReceiver = InboxReceiver::where('id', $args['id'])->first();

        if (!$inboxReceiver) {
            throw new CustomException(
                'Inbox not found',
                'Inbox with this NId not found'
            );
        }

        //Check the inbox is readed or not
        $this->markAsRead($inboxReceiver, $context);

        return $inboxReceiver;
    }

    /**
     * markAsRead
     *
     * @param Object $inboxReceiver
     * @param Object $context
     *
     * @return boolean
     */
    public function markAsRead($inboxReceiver, $context)
    {
        if ($inboxReceiver->StatusReceive != 'read') {
            InboxReceiver::where('id', $inboxReceiver->id)
                        ->update(['StatusReceive' => 'read']);
        }

        return true;
    }
}
