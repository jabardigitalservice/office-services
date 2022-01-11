<?php

namespace App\GraphQL\Types;

use App\Models\InboxReceiverCorrection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InboxReceiverCorrectionType
{
    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return array
     */
    public function isActioned($rootValue, array $args, GraphQLContext $context)
    {
        $peopleId = auth()->user()->PeopleId;
        $receiver = InboxReceiverCorrection::where('NId', $rootValue->NId)
            ->where('To_Id', $peopleId)
            ->first();

        $sender = InboxReceiverCorrection::where('NId', $rootValue->NId)
            ->where('From_Id', $peopleId)
            ->first();

        if ($receiver && $sender) {
            return true;
        }

        return false;
    }
}
