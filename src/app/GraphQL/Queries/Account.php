<?php

namespace App\GraphQL\Queries;

use App\Models\People;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Account
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
    public function profile($rootValue, array $args, GraphQLContext $context)
    {
        return $context->request()->people;
    }
}
