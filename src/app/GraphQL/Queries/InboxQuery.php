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
        $userPosition = $context->user->PeoplePosition;
        $positionGroups = array_merge(
            config('constants.peoplePositionGroups.2'),
            config('constants.peoplePositionGroups.9')
        );

        $found = $this->isFoundUserPosition($userPosition, $positionGroups);
        if ($found) {
            $regionalCount = $this->unreadCountDeptQuery($context);
        } else {
            $regionalCount = $this->unreadCountQuery(InboxReceiverScopeType::REGIONAL(), $context);
        }

        $internalCount = $this->unreadCountQuery(InboxReceiverScopeType::INTERNAL(), $context);
        $dispositionCount = $this->unreadCountQuery(InboxReceiverScopeType::DISPOSITION(), $context);

        $count = [
            'regional' => $regionalCount,
            'internal' => $internalCount,
            'disposition' => $dispositionCount
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

        $operator = $this->getRoleOperator($scope);

        $query = InboxReceiver::where('RoleId_To', $user->PrimaryRoleId)
            ->where('StatusReceive', 'unread')
            ->whereHas('sender', function ($senderQuery) use ($deptCode, $operator) {
                $senderQuery->whereHas('role', function ($roleQuery) use ($deptCode, $operator) {
                    $roleQuery->where('RoleCode', $operator, $deptCode);
                });
            });

        if ((string) $user->GroupId != PeopleGroupTypeEnum::TU()) {
            $query->where('To_Id', $user->PeopleId);
        }

        if ($scope == InboxReceiverScopeType::DISPOSITION()) {
            $query->where('ReceiverAs', 'cc1');
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

        if ((string) $user->GroupId != PeopleGroupTypeEnum::TU()) {
            $query->where('To_Id', $user->PeopleId);
        }

        return $query->count();
    }

     /**
     * @param Array $positionList
     * @param String $position
     *
     * @return Boolean
     */
    private function isFoundUserPosition($userPosition, $positionList)
    {
        foreach ($positionList as $position) {
            if (strpos($userPosition, $position) !== false) {
                return true;
            }
        }

        return false;
    }

     /**
     * @param String $scope
     *
     * @return Strin
     */
    private function getRoleOperator($scope)
    {
        switch ($scope) {
            case InboxReceiverScopeType::REGIONAL():
                return '!=';

            case InboxReceiverScopeType::INTERNAL():
            case InboxReceiverScopeType::DISPOSITION():
                return '=';
        }
    }
}
