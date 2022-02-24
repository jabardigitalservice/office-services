<?php

namespace App\Models;

use App\Enums\CustomReceiverTypeEnum;
use App\Enums\DraftObjectiveTypeEnum;
use App\Enums\ListTypeEnum;
use App\Http\Traits\InboxFilterTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxReceiverCorrection extends Model
{
    use HasFactory;
    use InboxFilterTrait;

    protected $connection = 'sikdweb';

    protected $table = 'inbox_receiver_koreksi';

    public $timestamps = false;

    protected $fillable = [
        'NId',
        'NKey',
        'GIR_Id',
        'From_Id',
        'RoleId_From',
        'To_Id',
        'RoleId_To',
        'ReceiverAs',
        'Msg',
        'StatusReceive',
        'ReceiveDate',
        'To_Id_Desc',
        'RoleCode',
        'JenisId',
        'id_koreksi'
    ];

    public function draftDetail()
    {
        return $this->belongsTo(Draft::class, 'NId', 'NId_Temp');
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'From_Id', 'PeopleId');
    }

    public function receiver()
    {
        return $this->belongsTo(People::class, 'To_Id', 'PeopleId');
    }

    public function correction()
    {
        return $this->belongsTo(InboxCorrection::class, 'NId', 'NId');
    }

    public function personalAccessTokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id', 'To_Id');
    }

    public function setReceiveDateAttribute($value)
    {
        $this->attributes['ReceiveDate'] = $value->copy()->addHours(7);
    }

    public function setGirIdAttribute($value)
    {
        // GirId = peopleId + now (date in 'dmyhis' format)
        // 19 means the datetime characters numbers
        $peopleId = substr($value, 0, -19);
        $dateString = substr($value, -19);
        $date = parseDateTimeFormat($dateString, 'dmyhis');

        $this->attributes['GIR_Id'] = $peopleId . $date;
    }

    public function search($query, $search)
    {
        $query->whereIn('NId', function ($inboxQuery) use ($search) {
            $inboxQuery->select('NId_Temp')
                ->from('konsep_naskah')
                ->where('Hal', 'LIKE', '%' . $search . '%');
        });

        return $query;
    }

    public function grouping($query, $grouping)
    {
        if ($grouping) {
            $query->distinct('NId');
        }
        return $query;
    }

    public function objective($query, $objective)
    {
        $userId = auth()->user()->PeopleId;
        $query->whereIn('NId', function ($draftQuery) {
            $draftQuery->select('NId_Temp')
                ->from('konsep_naskah');
        });

        switch ($objective) {
            case DraftObjectiveTypeEnum::IN():
                $query->where('From_Id', '!=', $userId)
                    ->where('ReceiverAs', '!=', 'to_koreksi');
                break;

            case DraftObjectiveTypeEnum::OUT():
                $query->where('To_Id', '!=', $userId);
                break;

            case DraftObjectiveTypeEnum::REVISE():
                $query->where('From_Id', $userId)
                    ->where('To_Id', $userId);
                break;
        }
        return $query;
    }

    public function filter($query, $filter)
    {
        $this->filterByStatus($query, $filter);
        $this->filterByType($query, $filter, ListTypeEnum::DRAFT_LIST());
        $this->filterByUrgency($query, $filter, ListTypeEnum::DRAFT_LIST());
        $this->filterDraftByReceiverTypes($query, $filter);
        return $query;
    }

    private function filterDraftByReceiverTypes($query, $filter)
    {
        $receiverTypes = $filter["receiverTypes"] ?? null;
        if ($receiverTypes) {
            $arrayReceiverTypes = explode(", ", $receiverTypes);
            $receiverAs = $this->getReceiverAsData($arrayReceiverTypes);
            if (in_array(CustomReceiverTypeEnum::REVIEW(), $arrayReceiverTypes)) {
                $this->receiverReviewQuery($query, $receiverAs);
            } else {
                $this->receiverDefaultQuery($query, $receiverAs);
                $this->receiverSignQuery($query, $arrayReceiverTypes);
            }
        }
    }

    private function receiverDefaultQuery($query, $receiverTypes)
    {
        $query->whereIn('ReceiverAs', $receiverTypes);
    }

    private function receiverReviewQuery($query, $receiverTypes)
    {
        $query->whereIn('NId', fn($query) => $query->select('NId_Temp')
            ->from('konsep_naskah')
            ->whereNotIn('Konsep', ['0', '2']))
            ->where(fn($query) => $query->whereIn('ReceiverAs', $receiverTypes)
            ->orWhere(fn($query) => $query->where('ReceiverAs', 'meneruskan')
                ->whereIn('NId', fn($query) => $query->select('NId_Temp')
                    ->from('konsep_naskah')
                    ->where(fn($query) => $query->where('nosurat', '=', null)
                        ->orWhere('nosurat', '!=', null)
                        ->where('Approve_People', '!=', auth()->user()->PeopleId)))));
    }

    private function receiverSignQuery($query, $receiverTypes)
    {
        $this->signedOperatorSelect($query, $receiverTypes);
        if (in_array(CustomReceiverTypeEnum::SIGN_REQUEST(), $receiverTypes)) {
            $query->whereIn('NId', fn($query) => $query->select('NId_Temp')
                ->from('konsep_naskah')
                ->where('nosurat', '!=', null)
                ->where('Approve_People', '=', auth()->user()->PeopleId));
        }
    }

    private function signedOperatorSelect($query, $receiverTypes)
    {
        if (in_array(CustomReceiverTypeEnum::SIGNED(), $receiverTypes)) {
            $query->whereIn('NId', fn($query) => $query->select('NId_Temp')
                ->from('konsep_naskah')
                ->whereIn('Konsep', ['0', '2']));
        } elseif (in_array(CustomReceiverTypeEnum::SIGN_REQUEST(), $receiverTypes)) {
            $query->whereIn('NId', fn($query) => $query->select('NId_Temp')
                ->from('konsep_naskah')
                ->whereNotIn('Konsep', ['0', '2']));
        }
    }

    private function getReceiverAsData($arrayReceiverTypes)
    {
        $receiverAs = [];
        foreach ($arrayReceiverTypes as $receiverType) {
            $receiverType = match ($receiverType) {
                CustomReceiverTypeEnum::CORRECTION()->value    => ['koreksi'],
                CustomReceiverTypeEnum::NUMBERING()->value     => ['Meminta Nomber Surat'],
                CustomReceiverTypeEnum::SIGN_REQUEST()->value,
                CustomReceiverTypeEnum::SIGNED()->value        => ['meneruskan', 'approvenaskah'],
                default => $this->getReceiverAsReviewData()
            };
            $receiverAs = array_merge($receiverAs, $receiverType);
        }
        return $receiverAs;
    }

    public function getReceiverAsReviewData()
    {
        return [
            'to_draft_keluar',
            'to_draft_notadinas',
            'to_draft_edaran',
            'to_draft_sprint',
            'to_draft_instruksi_gub',
            'to_draft_sket',
            'to_draft_super_tugas',
            'to_draft_pengumuman',
            'to_draft_surat_izin',
            'to_draft_rekomendasi'
        ];
    }

    public function getReceiverAsLabelAttribute()
    {
        $label = match ($this->ReceiverAs) {
            'approvenaskah'         => 'TTE Naskah',
            'meneruskan'            => 'Review Naskah',
            'Meminta Nomber Surat'  => 'Penomoran Naskah',
            'koreksi'               => 'Perbaiki Naskah',
            default                 => 'Review Naskah'
        };

        return $label;
    }
}
