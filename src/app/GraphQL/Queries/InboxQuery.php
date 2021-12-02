<?php

namespace App\GraphQL\Queries;

use App\Enums\InboxReceiverScopeType;
use App\Enums\PeopleGroupTypeEnum;
use App\Exceptions\CustomException;
use App\Models\InboxReceiver;
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
        $inboxReceiver = InboxReceiver::find($args['id']);

        if (!$inboxReceiver) {
            throw new CustomException(
                'Inbox not found',
                'Inbox with this NId not found'
            );
        }

        $inboxReceiver->StatusReceive = 'read';
        $inboxReceiver->save();

        return $inboxReceiver;
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
    public function unreadCount($rootValue, array $args, GraphQLContext $context)
    {
        if (strpos($context->user->PeoplePosition, 'KEPALA DINAS') !== false ||
            strpos($context->user->PeoplePosition, 'SEKRETARIS DINAS') !== false) {
            $regionalCount = $this->unreadCountDeptQuery($context);
        } else {
            $regionalCount = $this->unreadCountQuery(InboxReceiverScopeType::REGIONAL(), $context);
        }

        $internalCount = $this->unreadCountQuery(InboxReceiverScopeType::INTERNAL(), $context);

        $count = [
            'regional' => $regionalCount,
            'internal' => $internalCount
        ];

        return $count;
    }

    /**
     * @param String scope
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return Integer
     */
    private function unreadCountQuery($scope, GraphQLContext $context)
    {
        $user = $context->user;
        $deptCode = $user->role->RoleCode;

        $operator = '';
        switch ($scope) {
            case InboxReceiverScopeType::REGIONAL():
                $operator = '!=';
                break;

            case InboxReceiverScopeType::INTERNAL():
                $operator = '=';
                break;
        }

        $query = InboxReceiver::where('RoleId_To', $user->PrimaryRoleId)
            ->where('StatusReceive', 'unread')
            ->whereHas('sender', function($senderQuery) use ($deptCode, $operator) {
                $senderQuery->whereHas('role', function($roleQuery) use ($deptCode, $operator) {
                    $roleQuery->where('RoleCode', $operator, $deptCode);
                });
            });

        if ((String) $user->GroupId != PeopleGroupTypeEnum::TU()) {
            $query->where('To_Id', $user->PeopleId);
        }

        return $query->count();
    }

     /**
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return Integer
     */
    private function unreadCountDeptQuery($context)
    {
        $user = $context->user;
        $query = InboxReceiver::where('RoleId_To', $user->PrimaryRoleId)
            ->where('StatusReceive', 'unread')
            ->where('ReceiverAs', 'to_forward');

        if ((String) $user->GroupId != PeopleGroupTypeEnum::TU()) {
            $query->where('To_Id', $user->PeopleId);
        }

        return $query->count();
    }
}
