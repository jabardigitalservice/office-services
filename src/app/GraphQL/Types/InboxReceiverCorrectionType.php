<?php

namespace App\GraphQL\Types;

use App\Models\InboxReceiverCorrection;
use App\Enums\DocumentSignatureSentNotificationTypeEnum;
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
        $receiver = $this->getDraftByOriginPeopleType(
            $rootValue,
            DocumentSignatureSentNotificationTypeEnum::RECEIVER()
        );
        $sender = $this->getDraftByOriginPeopleType(
            $rootValue,
            DocumentSignatureSentNotificationTypeEnum::SENDER()
        );

        if ($receiver && $sender) {
            return true;
        }
        return false;
    }

    /**
     * Get draft record by the sender or receiver
     * @param $rootValue
     * @param DocumentSignatureSentNotificationTypeEnum $type
     *
     * @return InboxReceiverCorrection
     */
    private function getDraftByOriginPeopleType($rootValue, $type)
    {
        $peopleId = auth()->user()->PeopleId;
        if ($type == DocumentSignatureSentNotificationTypeEnum::RECEIVER()) {
            $field = 'To_Id';
        } else {
            $field = 'From_Id';
        }

        return InboxReceiverCorrection::where('NId', $rootValue->NId)
            ->where($field, $peopleId)
            ->first();
    }
}
