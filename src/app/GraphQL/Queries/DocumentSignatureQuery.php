<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
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
    public function list($rootValue, array $args, GraphQLContext $context)
    {
        $data = DocumentSignatureSent::where('PeopleIDTujuan', auth()->user()->PeopleId)
                                    ->orderBy('tgl', 'DESC')
                                    ->get();

        if (!$data) {
            throw new CustomException(
                'Document not found',
                'Document with this user not found'
            );
        }

        $documentSignatureSent = [];
        foreach ($data as $_data) {
            if ($_data->urutan > 1) {
                $checkParent = DocumentSignatureSent::where('ttd_id', $_data->ttd_id)
                                                    ->where('urutan', $_data->urutan-1)
                                                    ->first();
                if ($checkParent->status == 0) {
                    continue;
                }
            }

            array_push($documentSignatureSent, $_data);
        }

        return collect($documentSignatureSent);
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
