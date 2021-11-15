<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\DocumentSignatureForward;
use App\Models\DocumentSignatureSent;
use App\Models\DocumentSignatureSentRead;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DocumentSignatureHistoryQuery
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
    public function history($rootValue, array $args, GraphQLContext $context)
    {
        $documentSignatureSent = DocumentSignatureSent::where('ttd_id', $args['documentSignatureId'])
                                    ->orderBy('urutan', 'ASC')
                                    ->get();


        if (!$documentSignatureSent) {
            throw new CustomException(
                'Document not found',
                'Document with this user not found'
            );
        }

        $documentSignatureForward = DocumentSignatureForward::where('ttd_id', $args['documentSignatureId'])
                                    ->orderBy('urutan', 'ASC')
                                    ->get();

        $data = collect([
            'documentSignatureForward' => $documentSignatureForward,
            'documentSignatureSent' => $documentSignatureSent,
        ]);

        return $data;
    }
}
