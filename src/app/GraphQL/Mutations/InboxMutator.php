<?php

namespace App\GraphQL\Mutations;

use App\Enums\FcmNotificationActionTypeEnum;
use App\Enums\PeopleProposedTypeEnum;
use App\Http\Traits\SendNotificationTrait;
use App\Models\Draft;
use App\Models\Inbox;
use App\Models\InboxDisposition;
use App\Models\InboxReceiver;
use App\Models\InboxReceiverCorrection;
use App\Models\People;
use App\Models\TableSetting;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class InboxMutator
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
        // Forward is the default action
        $action = Arr::get($args, 'input.action') ?? PeopleProposedTypeEnum::FORWARD();
        $stringReceiversIds = Arr::get($args, 'input.receiversIds');
        $time = Carbon::now();
        $dispositionType = str_replace(", ", "|", Arr::get($args, 'input.dispositionType'));

        $inboxData = [
            'from' => auth()->user(),
            'inboxId' => Arr::get($args, 'input.inboxId'),
            'message' => Arr::get($args, 'input.message'),
            'urgency' => Arr::get($args, 'input.urgency'),
            'receiversIds' => explode(", ", $stringReceiversIds),
            'time' => $time,
            'groupId' => auth()->user()->PeopleId . $time,
            'dispositionType' => $dispositionType,
        ];

        $inboxReceivers = [];
        foreach ($inboxData['receiversIds'] as $receiverId) {
            $newInboxReceiver = $this->createInboxReceiver($inboxData, $receiverId, $action);
            $this->createInboxReceiverCorrection($inboxData, $receiverId, $action);
            array_push($inboxReceivers, $newInboxReceiver);
        }

        $this->createInboxDisposition($inboxData, $action);
        $this->markActioned($inboxData);
        $this->actionNotification($inboxData, $action);
        return $inboxReceivers;
    }

    /**
     * @param $rootValue
     * @param $args
     *
     * @throws \Exception
     *
     * @return String
     */
    public function endForward($rootValue, array $args)
    {
        $peopleId = auth()->user()->PeopleId;
        $inboxId = Arr::get($args, 'inboxId');

        InboxReceiver::where('NId', $inboxId)
            ->where('To_Id', strval($peopleId))
            ->firstOrFail()
            ->update(['Status' => 1, 'TindakLanjut' => 1]);

        return 'status updated';
    }

    /**
     * @param Array $inboxData
     * @param String $receiverId
     * @param String $action
     *
     * @return InboxReceiver
     */
    private function createInboxReceiver($inboxData, $receiverId, $action)
    {
        $inboxReceiver = $this->generateInboxReceiverData($inboxData, $receiverId, $action);
        $inboxReceiver['ReceiverAs'] = $this->generateLabel($action);
        if (array_key_exists('draft', $inboxReceiver)) {
            $inboxReceiver['ReceiverAs'] = $this->generateDraftLabel($inboxReceiver['draft']);
        }
        return InboxReceiver::create($inboxReceiver);
    }

    /**
     * @param Array    $inboxData
     *
     * @return void
     */
    private function markActioned($inboxData)
    {
        $inboxId = $inboxData['inboxId'];
        $fromId = $inboxData['from']->PeopleId;

        $inbox = InboxReceiver::where('NId', $inboxId)
            ->where('To_Id', strval($fromId))
            ->firstOrFail();

        if ($inbox->Status != 1) {
            InboxReceiver::where('NId', $inboxId)
                ->where('To_Id', strval($fromId))
                ->update(['Status' => 1]);
        }
    }

    /**
     * @param Array $inboxData
     * @param String $action
     *
     * @return void
     */
    private function actionNotification($inboxData, $action)
    {
        if (!$this->isDraftScope($action)) {
            $inbox = Inbox::findOrFail($inboxData['inboxId']);

            $peopleId = substr($inboxData['groupId'], 0, -19);
            $dateString = substr($inboxData['groupId'], -19);
            $date = parseDateTimeFormat($dateString, 'dmyhis');

            if ($action == PeopleProposedTypeEnum::FORWARD()) {
                $createdBy = Inbox::where('NId', $inboxData['inboxId'])->first()->createdBy;
                $title = '';
                $body = $createdBy->role->rolecode->rolecode_sort . ' telah mengirimkan surat terkait dengan ' . $inbox->Hal . '. Klik disini untuk membaca dan menindaklanjuti pesan.';
                $actionMessage = FcmNotificationActionTypeEnum::INBOX_DETAIL();
            } elseif ($action == PeopleProposedTypeEnum::DISPOSITION()) {
                $sender = auth()->user()->PeopleName;
                $title = 'Disposisi Naskah';
                $body = $sender . ' telah mendisposisikan ' . $inbox->type->JenisName . ' terkait dengan ' . $inbox->Hal . '. Klik disini untuk membaca dan menindaklanjuti pesan.';
                $actionMessage = FcmNotificationActionTypeEnum::DISPOSITION_DETAIL();
            }

            $messageAttribute = [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'inboxId' => $inboxData['inboxId'],
                    'groupId' => $peopleId . $date,
                    'peopleIds' => $inboxData['receiversIds'],
                    'action' => $actionMessage,
                ]
            ];

            $this->setupInboxReceiverNotification($messageAttribute);
        }
    }

    /**
     * Create an inboxDisposition
     * if the action is DISPOSITION
     *
     * @param Array $inboxData
     * @param String $action
     *
     * @return Void
     */
    private function createInboxDisposition($inboxData, $action)
    {
        if ($action == PeopleProposedTypeEnum::DISPOSITION()) {
            $inboxDisposition = [
                'NId'       => $inboxData['inboxId'],
                'GIR_Id'    => $inboxData['groupId'],
                'Sifat'     => $inboxData['urgency'],
                'Disposisi' => $inboxData['dispositionType'],
                'RoleId'    => $inboxData['from']->PrimaryRoleId,
            ];
            InboxDisposition::create($inboxDisposition);
        }
    }

    /**
     * Create an inboxReceiverCorrection
     * if the action is DRAFT_FORWARD
     *
     * @param Array $inboxData
     * @param String $action
     *
     * @throws \Exception
     *
     * @return Void
     */
    private function createInboxReceiverCorrection($inboxData, $receiverId, $action)
    {
        if ($this->isDraftScope($action)) {
            $inboxReceiverCorrection = $this->generateInboxReceiverData($inboxData, $receiverId, $action);
            $inboxReceiverCorrection['ReceiverAs'] = $this->generateLabel($action);
            InboxReceiverCorrection::create($inboxReceiverCorrection);
            $this->updateOriginDraft(
                $inboxReceiverCorrection['receiver'],
                $inboxReceiverCorrection['draft'],
                $action
            );
        }
    }

    /**
     * Update the origin draft
     *
     * @param People $receiver
     * @param Draft $draft
     * @param String $action
     *
     * @throws \Exception
     *
     * @return Void
     */
    private function updateOriginDraft($receiver, $draft, $action)
    {
        $user = auth()->user();
        $updatedDraft = [
            'RoleId_From' => $user->PrimaryRoleId,
            'Approve_People' => $receiver->PeopleId
        ];
        if ($action == PeopleProposedTypeEnum::FORWARD_DRAFT()) {
            $updatedDraft['Nama_ttd_konsep'] = $receiver->PeopleName;
        }
        $draft->update($updatedDraft);
    }

    /**
     * Generate inbox receiver data
     * Wheter inboxReceiver or inboxReceiverCorrection
     *
     * @param Array $inboxData
     * @param String $action
     *
     * @return Array
     */
    private function generateInboxReceiverData($inboxData, $receiverId, $action)
    {
        $receiver = People::findOrFail($receiverId);
        $nkey = TableSetting::first()->tb_key;
        $draft = $this->findDraft($inboxData, $action);
        $data = [
            'NId'           => $inboxData['inboxId'],
            'NKey'          => $nkey,
            'GIR_Id'        => $inboxData['groupId'],
            'From_Id'       => $inboxData['from']->PeopleId,
            'RoleId_From'   => $inboxData['from']->PrimaryRoleId,
            'To_Id'         => $receiverId,
            'RoleId_To'     => $receiver->PrimaryRoleId,
            'Msg'           => $inboxData['message'],
            'StatusReceive' => 'unread',
            'ReceiveDate'   => $inboxData['time'],
            'To_Id_Desc'    => $receiver->role->RoleDesc,
            'Status'        => 0,
        ];

        if ($draft) {
            $data['draft'] = $draft;
            $data['receiver'] = $receiver;
        }

        return $data;
    }

    /**
     * Find draft
     * if the action is FORWARD_DRAFT
     *
     * @param String $action
     *
     * @throws \Exception
     *
     * @return Draft
     */
    private function findDraft($inboxData, $action)
    {
        if ($this->isDraftScope($action)) {
            return Draft::where('NId_Temp', $inboxData['inboxId'])->firstOrFail();
        }
    }

    /**
     * Generate inbox label according to action type
     * @param String $action
     * @param Draft $draft
     *
     * @return String
     */
    private function generateLabel($action)
    {
        $label = match ($action) {
            PeopleProposedTypeEnum::DISPOSITION()->value    => 'cc1',
            PeopleProposedTypeEnum::FORWARD_DRAFT()->value  => 'meneruskan',
            PeopleProposedTypeEnum::NUMBERING_UK()->value,
            PeopleProposedTypeEnum::NUMBERING_TU()->value   => 'Meminta Nomber Surat',
            default                                         => 'to_forward'
        };
        return $label;
    }

    /**
     * Generate draft label
     * @param Draft $draft
     *
     * @return String
     */
    private function generateDraftLabel($draft)
    {
        $label = match ($draft->Ket) {
            'outboxnotadinas'       => 'to_draft_notadinas',
            'outboxsprint'          => 'to_draft_sprint',
            'outboxsprintgub'       => 'to_draft_sprintgub',
            'outboxundangan'        => 'to_draft_undangan',
            'outboxedaran'          => 'to_draft_edaran',
            'outboxinstruksigub'    => 'to_draft_instruksi_gub',
            'outboxsupertugas'      => 'to_draft_super_tugas',
            'outboxkeluar'          => 'to_draft_keluar',
            'outboxsket'            => 'to_draft_sket',
            'outboxpengumuman'      => 'to_draft_pengumuman',
            'outboxsuratizin'       => 'to_draft_surat_izin',
            'outboxrekomendasi'     => 'to_draft_rekomendasi',
            default                 => 'to_draft_nadin',
        };
        return $label;
    }

    /**
     * Check if the action is on Draft scope
     * @param String $action
     *
     * @return Boolean
     */
    private function isDraftScope($action)
    {
        return match ($action) {
            PeopleProposedTypeEnum::FORWARD_DRAFT()->value,
            PeopleProposedTypeEnum::NUMBERING_UK()->value,
            PeopleProposedTypeEnum::NUMBERING_TU()->value   => true,
            default                                         => false
        };
    }
}
