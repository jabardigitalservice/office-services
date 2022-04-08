<?php

namespace App\GraphQL\Queries;

use App\Enums\SignatureStatusTypeEnum;
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
                                                    ->where('urutan', $_data->urutan - 1)
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
        if ($documentSignatureSent->PeopleIDTujuan == auth()->user()->PeopleId) {
            $documentSignatureSent->is_receiver_read = true;
        }

        if ($documentSignatureSent->PeopleID == auth()->user()->PeopleId) {
            $documentSignatureSent->is_sender_read = true;
        }

        $documentSignatureSent->save();

        return $documentSignatureSent;
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
    public function timelines($rootValue, array $args, GraphQLContext $context)
    {
        $documentSignatureIds = explode(", ", $args['filter']['documentSignatureIds']);

        $items = [];
        foreach ($documentSignatureIds as $documentSignatureId) {
            $sort = $args['filter']['sort'] ?? null;
            $status = $args['filter']['status'] ?? null;

            $documentSignature = DocumentSignatureSent::where('ttd_id', $documentSignatureId)
                                                        ->where('urutan', '<', $sort);

            if ($status) {
                if ($status == SignatureStatusTypeEnum::SIGNED()) {
                    $documentSignature->where('status', SignatureStatusTypeEnum::SUCCESS()->value);
                }
                if ($status == SignatureStatusTypeEnum::UNSIGNED()) {
                    $documentSignature->whereIn(
                        'status',
                        [
                            SignatureStatusTypeEnum::WAITING()->value,
                            SignatureStatusTypeEnum::REJECT()->value
                        ]
                    );
                }
            }

            $documentSignature = $documentSignature->orderBy('urutan', 'DESC')->get();

            array_push($items, [
                'documentSignatuerSents' => $documentSignature
            ]);
        }

        return $items;
    }
}
