<?php

namespace App\GraphQL\Directives;

use App\Exceptions\CustomException;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProtectDirective extends BaseDirective implements FieldMiddleware
{
    public static function definition(): string
    {
        return /** @lang GraphQL */ <<<'GRAPHQL'
            directive @protect on FIELD_DEFINITION
        GRAPHQL;
    }

    public function handleField(FieldValue $fieldValue, Closure $next): FieldValue
    {
        $resolver = $fieldValue->getResolver();

        $fieldValue->setResolver(function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver) {
            if (!$context->request()->people) {
                throw new CustomException(
                    'Unauthorized',
                    'No token'
                );
            }
            $result = $resolver($root, $args, $context, $resolveInfo);

            return $result;
        });
        return $next($fieldValue);
    }
}
