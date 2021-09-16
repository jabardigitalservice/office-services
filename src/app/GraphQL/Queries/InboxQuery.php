<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\Inbox;
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
        $inbox = Inbox::find($args['NId']);

        if ($inbox) {
            InboxReceiver::where('NId', $inbox->NId)
                        ->update(['StatusReceive' => 'read']);

            return $inbox;
        } else {
            throw new CustomException(
                'Inbox not found',
                'Inbox with this NId not found'
            );
        }
    }
}
