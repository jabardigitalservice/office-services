<?php

namespace App\GraphQL\Types;

use App\Models\InboxReceiver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InboxReceiverType
{
    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return array
     */
    public function isEndForward($rootValue, array $args, GraphQLContext $context)
    {
        $isForwarded = InboxReceiver::where('NId', $rootValue->NId)
            ->where('RoleId_To', auth()->user()->PrimaryRoleId)
            ->where('Status', 1)
            ->first();

        $hasForwardedSender = InboxReceiver::where('NId', $rootValue->NId)
            ->where('RoleId_From', auth()->user()->PrimaryRoleId)
            ->first();

        if ($isForwarded && !$hasForwardedSender) {
            return true;
        }

        return false;
    }
}
