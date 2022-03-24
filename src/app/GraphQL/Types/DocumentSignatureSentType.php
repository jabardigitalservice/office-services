<?php

namespace App\GraphQL\Types;

use App\Models\DocumentSignatureSent;
use App\Models\InboxReceiver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DocumentSignatureSentType
{
    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return array
     */
    public function isLastSigned($rootValue, array $args, GraphQLContext $context)
    {
        $isLastSigned = DocumentSignatureSent::where('ttd_id', $rootValue->ttd_id)
            ->where('PeopleID', $rootValue->PeopleID)
            ->where('urutan', $rootValue->urutan + 1)
            ->first();

        if ($isLastSigned) {
            return false;
        }

        return true;
    }

    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return array
     */
    public function parent($rootValue, array $args, GraphQLContext $context)
    {
        $parent = DocumentSignatureSent::where('ttd_id', $rootValue->ttd_id)
            ->where('PeopleID', $rootValue->PeopleID)
            ->where('urutan', $rootValue->urutan - 1)
            ->first();

        if ($parent) {
            return $parent;
        }

        return null;
    }

     /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     *
     * @return array
     */
    public function isRead($rootValue, array $args, GraphQLContext $context)
    {
        if ($rootValue->PeopleIDTujuan == auth()->user()->PeopleId && $rootValue->is_receiver_read == true) {
            return true;
        } elseif ($rootValue->PeopleID == auth()->user()->PeopleId  && $rootValue->is_sender_read == true) {
            return true;
        } else {
            return false;
        }
    }
}
