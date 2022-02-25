<?php

namespace App\GraphQL\Mutations;

use App\Enums\DocumentSignatureSentNotificationTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Exceptions\CustomException;
use App\Models\DocumentSignatureForward;
use App\Models\DocumentSignatureSent;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class DocumentSignatureForwardMutator
{
    use SendNotificationTrait;

    /**
     * @param $rootValue
     * @param $args
     *
     * @throws \Exception
     *
     * @return array
     */
    public function forward($rootValue, array $args)
    {
        $documentSignatureSentId = Arr::get($args, 'input.documentSignatureSentId');
        $documentSignatureSent = DocumentSignatureSent::where('id', $documentSignatureSentId)
                                    ->first();

        $documentSignatureForwardIds = $this->doForward($documentSignatureSent, $args);

        if (!$documentSignatureForwardIds) {
            throw new CustomException(
                'Forward document failed',
                'Return ids is missing. Please try again.'
            );
        }

        $this->doSendNotification($documentSignatureSent->id, $documentSignatureSent->receiver->PeopleName);

        return $documentSignatureForwardIds;
    }

    /**
     * doSendNotification
     *
     * @param  string $id
     * @param  string $name
     * @return void
     */
    protected function doSendNotification($id, $name)
    {
        $messageAttribute = [
            'notification' => [
                'title' => 'TTE Naskah',
                'body' => 'Naskah Anda telah di tandatangani oleh ' . $name . '. Klik disini untuk lihat naskah!',
            ],
            'data' => [
                'documentSignatureSentId' => $id,
                'target' => DocumentSignatureSentNotificationTypeEnum::SENDER()
            ]
        ];

        $this->setupDocumentSignatureSentNotification($messageAttribute);
    }

    /**
     * doForward
     *
     * @param  object $documentSignatureSentId
     * @param  string $sender
     * @param  mixed $args
     * @return array
     */
    public function doForward($documentSignatureSent, $args)
    {
        $note = Arr::get($args, 'input.note');
        $ids = array();
        $receiver = $this->forwardReceiver($documentSignatureSent);

        if ($receiver != null) {
            foreach ($receiver as $key => $receiver) {
                $key++;
                $documentSignatureForward = DocumentSignatureForward::create([
                    'ttd_id' => $documentSignatureSent->ttd_id,
                    'catatan' => $note,
                    'tgl' => Carbon::now(),
                    'PeopleID' => $documentSignatureSent->PeopleIDTujuan,
                    'PeopleIDTujuan' => $receiver,
                    'urutan' => $key,
                    'status' => SignatureStatusTypeEnum::WAITING()->value,
                ]);

                array_push($ids, $documentSignatureForward);
            }

            return $ids;
        }

        return false;
    }

    /**
     * forwardReceiver
     *
     * @param  mixed $type
     * @return mixed
     */
    public function forwardReceiver($documentSignatureSent)
    {
        $type = optional($documentSignatureSent->documentSignature->documentSignatureType)->document_forward_target;
        switch ($type) {
            case 'UK':
                $receiver = People::whereHas('role', function ($role) {
                    $role->where('RoleCode', auth()->user()->role->RoleCode);
                    $role->where('GRoleId', auth()->user()->role->GRoleId);
                })->where('GroupId', PeopleGroupTypeEnum::UK()->value)->pluck('PeopleId');
                break;

            case 'TU':
                $receiver = People::whereHas('role', function ($role) {
                    $role->where('RoleCode', auth()->user()->role->RoleCode);
                    $role->where('Code_Tu', auth()->user()->role->Code_Tu);
                })->where('GroupId', PeopleGroupTypeEnum::TU()->value)->pluck('PeopleId');
                break;

            default:
                $receiver = null;
                break;
        }

        return $receiver;
    }
}
