<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\DocumentSignatureSent;
use App\Models\DocumentSignatureSentRead;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DocumentSignatureQuery
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
        $documentSignatureSent = DocumentSignatureSent::where('id', $args['id'])->first();

        if (!$documentSignatureSent) {
            throw new CustomException(
                'Document not found',
                'Document with this id not found'
            );
        }

        //Check the inbox is readed or not
        $this->markAsRead($documentSignatureSent, $context);

        return $documentSignatureSent;
    }

    /**
     * markAsRead
     *
     * @param Object $documentSignatureSent
     * @param Object $context
     *
     * @return boolean
     */
    public function markAsRead($documentSignatureSent, $context)
    {
        if (!$documentSignatureSent->documentSignatureSentRead) {
            $data = new DocumentSignatureSentRead;
            $data->document_signature_sent_id = $documentSignatureSent->id;
            $data->read = true;
            $data->save();
        }

        return true;
    }
}
