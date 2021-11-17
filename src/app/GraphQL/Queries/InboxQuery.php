<?php

namespace App\GraphQL\Queries;

use App\Enums\InboxReceiverScopeType;
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
        $regionalCount = $this->unreadCountQuery(InboxReceiverScopeType::REGIONAL(), $context);
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

        return InboxReceiver::where('RoleId_To', $user->PrimaryRoleId)
            ->where('StatusReceive', 'unread')
            ->whereHas('sender', function($senderQuery) use ($deptCode, $operator) {
                $senderQuery->whereHas('role', function($roleQuery) use ($deptCode, $operator) {
                    $roleQuery->where('RoleCode', $operator, $deptCode);
                });
            })->count();
    }
}
