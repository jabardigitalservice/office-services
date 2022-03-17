<?php

namespace App\GraphQL\Mutations;

use App\Enums\ActionLabelTypeEnum;
use App\Enums\FcmNotificationActionTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
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
        if (!$stringReceiversIds) {
            $stringReceiversIds = strval($this->getDefaultReceiver()->PeopleId);
        }

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
        $this->markActioned($inboxData, $action);

        // TEMPORARY: NUMBERING_UK/TU will not get the notification
        if (
            $action != PeopleProposedTypeEnum::NUMBERING_UK() &&
            $action != PeopleProposedTypeEnum::NUMBERING_TU()
        ) {
            $this->actionNotification($inboxData, $action);
        }
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
            ->update([
                'Status' => 1,
                'TindakLanjut' => 1,
                'action_label' => ActionLabelTypeEnum::FINISHED()
            ]);

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
    private function markActioned($inboxData, $action)
    {
        $inboxId = $inboxData['inboxId'];
        $fromId = $inboxData['from']->PeopleId;
        $actionLabel = $this->defineActionLabel($action);
        $currentInbox = InboxReceiver::where('NId', $inboxId)
            ->where('To_Id', strval($fromId));

        $currentInbox->update(['Status' => 1]);
        if ($this->isDraftScope($action)) {
            InboxReceiverCorrection::where('NId', $inboxId)
                ->where('To_Id', strval($fromId))
                ->update(['action_label' => $actionLabel]);
        } else {
            $currentInbox->update(['action_label' => $actionLabel]);
        }
    }

    /**
     * Define the action label for the updated recods
     * @param String $action
     *
     * @return String
     */
    private function defineActionLabel($action)
    {
        $label = match ($action) {
            PeopleProposedTypeEnum::DISPOSITION()->value    => ActionLabelTypeEnum::DISPOSED(),
            PeopleProposedTypeEnum::FORWARD()->value,
            PeopleProposedTypeEnum::FORWARD_DRAFT()->value  => ActionLabelTypeEnum::REVIEWED(),
            PeopleProposedTypeEnum::NUMBERING_UK()->value,
            PeopleProposedTypeEnum::NUMBERING_TU()->value   => ActionLabelTypeEnum::NUMBERING(),
            default                                         => null
        };
        return $label;
    }

    /**
     * Generate the action label for a new recod
     * @param String $action
     *
     * @return String
     */
    private function generateActionLabel($action)
    {
        $label = match ($action) {
            PeopleProposedTypeEnum::NUMBERING_UK()->value,
            PeopleProposedTypeEnum::NUMBERING_TU()->value   => ActionLabelTypeEnum::NUMBERING(),
            default                                         => null
        };
        return $label;
    }

    /**
     * @param Array $inboxData
     * @param String $action
     *
     * @return void
     */
    private function actionNotification($inboxData, $action)
    {
        $inbox = $this->definePrimaryModel($action, $inboxData['inboxId']);
        $dept = $inbox->createdBy->role->rolecode->rolecode_sort;
        $sender = auth()->user()->PeopleName;
        $title = '';
        $body = $dept . ' telah mengirimkan surat terkait dengan ' . $inbox->Hal;

        if ($action == PeopleProposedTypeEnum::FORWARD()) {
            $actionMessage = FcmNotificationActionTypeEnum::INBOX_DETAIL();
        } elseif ($action == PeopleProposedTypeEnum::DISPOSITION()) {
            $title = 'Disposisi Naskah';
            $body = $sender . ' telah mendisposisikan ' . $inbox->type->JenisName . ' terkait dengan ' . $inbox->Hal;
            $actionMessage = FcmNotificationActionTypeEnum::DISPOSITION_DETAIL();
        } elseif ($this->isDraftScope($action)) {
            $title = 'Review Naskah';
            $body = 'Terdapat ' . $inbox->type->JenisName . ' terkait dengan ' . $inbox->Hal . ' yang harus segera Anda periksa';
            $actionMessage = FcmNotificationActionTypeEnum::DRAFT_DETAIL();
        }

        $body = $body . '. Klik disini untuk membaca dan menindaklanjuti pesan.';
        $peopleId = substr($inboxData['groupId'], 0, -19);
        $dateString = substr($inboxData['groupId'], -19);
        $date = parseDateTimeFormat($dateString, 'dmyhis');

        $messageAttribute = [
            'notification' => [
                'title' => $title,
                'body' => str_replace('&nbsp;', ' ', strip_tags($body))
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

    /**
     * Define the primary table
     * wheter Inbox or Draft Model
     *
     * @param String $action
     *
     * @return Object
     */
    private function definePrimaryModel($action, $inboxId)
    {
        if ($this->isDraftScope($action)) {
            return Draft::findOrFail($inboxId);
        }
        return Inbox::findOrFail($inboxId);
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
            $inboxReceiverCorrection['action_label'] = $this->generateActionLabel($action);
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
     * Get default receiver
     * The default receiver is an UK
     *
     * @return People
     */
    private function getDefaultReceiver()
    {
        $receiver = People::where('GroupId', PeopleGroupTypeEnum::UK())
            ->where('PeoplePosition', 'like', "UNIT KEARSIPAN%");

        $userRole = auth()->user()->role->RoleName;
        if ($userRole == 'GUBERNUR JAWA BARAT' || $userRole == 'WAKIL GUBERNUR JAWA BARAT') {
            return $receiver->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
                ->from('role')
                ->where('Code_Tu', 'uk.setda'))
            ->first();
        }

        return $receiver->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
            ->from('role')
            ->where('GRoleId', auth()->user()->role->GRoleId))
        ->first();
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
