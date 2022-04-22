<?php

namespace App\GraphQL\Queries;

use App\Exceptions\CustomException;
use App\Models\DocumentSignature;
use App\Models\DocumentSignatureForward;
use App\Models\DocumentSignatureSent;
use App\Models\InboxReceiver;
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
                                    ->with(['sender', 'receiver'])
                                    ->orderBy('urutan', 'ASC')
                                    ->get();

        $documentSignatureForward = DocumentSignatureForward::where('ttd_id', $args['documentSignatureId'])
                                    ->with(['sender', 'receiver'])
                                    ->orderBy('urutan', 'ASC')
                                    ->get();

        $inboxId = null;
        if (count($documentSignatureSent) == 0) {
            $documentSignature = DocumentSignature::where('id', $args['documentSignatureId'])->first();
            if ($documentSignature) {
                $inboxId = optional($documentSignature->inboxFile)->NId;
            }
        } else {
            //select one document signature sent, get name file for relation to inbox file
            $inboxId = optional($documentSignatureSent->first()->documentSignature->inboxFile)->NId;
        }

        $documentSignatureDistribute = [];
        if ($inboxId) {
            $documentSignatureDistribute = InboxReceiver::where('NId', $inboxId)
                                        ->with(['sender', 'receiver'])
                                        ->where('ReceiverAs', 'to')
                                        ->get();
        }

        $data = collect([
            'documentSignatureDistribute' => $documentSignatureDistribute,
            'documentSignatureForward' => $documentSignatureForward,
            'documentSignatureSent' => $documentSignatureSent,
        ]);

        return $data;
    }
}
