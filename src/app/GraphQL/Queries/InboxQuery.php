<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Http\Resources\InboxHistoryCollection;
use App\Models\Inbox;
use App\Models\InboxDisposition;
use App\Models\InboxReceiver;
use App\Models\MasterDisposition;
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
                            ->where('To_Id', $context->request()->people->PeopleId)
                            ->first();

        if ($inboxReceiver) {
            if ($inboxReceiver->StatusReceive != 'read') {
                InboxReceiver::where('NId', $inboxReceiver->NId)
                            ->where('To_Id', $context->request()->people->PeopleId)
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

    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @throws \Exception
     *
     * @return array
     */
    public function history($rootValue, array $args, GraphQLContext $context)
    {
        $inboxReceiver = InboxReceiver::where('NId', $args['NId'])
                            ->where(function ($query) use ($context) {
                                $query->where('RoleId_To', 'like', $context->request()->people->PrimaryRoleId . '%');
                                $query->orWhere('RoleId_From', 'like', $context->request()->people->PrimaryRoleId . '%');
                            })
                            ->groupBy('GIR_Id')
                            ->orderBy('ReceiveDate', 'DESC')
                            ->get();

        if ($inboxReceiver) {
            return $inboxReceiver;
        } else {
            throw new CustomException(
                'Inbox not found',
                'Inbox with this NId not found'
            );
        }
    }
}
