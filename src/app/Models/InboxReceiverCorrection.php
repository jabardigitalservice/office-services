<?php

namespace App\Models;

use App\Enums\CustomReceiverTypeEnum;
use App\Enums\DraftObjectiveTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class InboxReceiverCorrection extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'inbox_receiver_koreksi';

    public $timestamps = false;

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
        $statuses = $filter["statuses"] ?? null;
        $types = $filter["types"] ?? null;
        $urgencies = $filter["urgencies"] ?? null;
        $receiverTypes = $filter["receiverTypes"] ?? null;

        if ($statuses) {
            $this->statusQuery($query, $statuses);
        }

        if ($types) {
            $this->typeQuery($query, $types);
        }

        if ($urgencies) {
            $this->urgencyQuery($query, $urgencies);
        }

        if ($receiverTypes) {
            $this->receiverQuery($query, $receiverTypes);
        }

        return $query;
    }

    private function statusQuery($query, $statuses)
    {
        $arrayStatuses = explode(", ", $statuses);
        $query->whereIn('StatusReceive', $arrayStatuses);
    }

    private function typeQuery($query, $types)
    {
        $tables = array(
            0 => array('name'  => 'konsep_naskah', 'column' => 'JenisId'),
            1 => array('name'  => 'master_jnaskah', 'column' => 'JenisId')
        );
        $this->threeLvlQuery($query, $types, $tables);
    }

    private function urgencyQuery($query, $urgencies)
    {
        $tables = array(
            0 => array('name'  => 'konsep_naskah', 'column' => 'UrgensiId'),
            1 => array('name'  => 'master_urgensi', 'column' => 'UrgensiName')
        );
        $this->threeLvlQuery($query, $urgencies, $tables);
    }

    private function threeLvlQuery($query, $requestFilter, $tables)
    {
        $arrayTypes = explode(", ", $requestFilter);
        $query->whereIn('NId', function ($draftQuery) use ($arrayTypes, $tables) {
            $draftQuery->select('NId_Temp')
            ->from(Arr::get($tables, '0.name'))
            ->whereIn(Arr::get($tables, '0.column'), function ($docQuery) use ($arrayTypes, $tables) {
                $docQuery->select(Arr::get($tables, '0.column'))
                    ->from(Arr::get($tables, '1.name'))
                    ->whereIn(Arr::get($tables, '1.column'), $arrayTypes);
            });
        });
    }

    private function receiverQuery($query, $receiverTypes)
    {
        $arrayReceiverTypes = explode(", ", $receiverTypes);
        $receiverAs = $this->getReceiverAsData($arrayReceiverTypes);
        if (in_array(CustomReceiverTypeEnum::REVIEW(), $arrayReceiverTypes)) {
            $this->receiverReviewQuery($query, $receiverAs);
        } else {
            $this->receiverDefaultQuery($query, $receiverAs);
            $this->receiverSignQuery($query, $arrayReceiverTypes);
        }
    }

    private function receiverDefaultQuery($query, $receiverTypes)
    {
        $query->whereIn('ReceiverAs', $receiverTypes)
            ->whereIn('NId', function ($draftQuery) {
                $draftQuery->select('NId_Temp')
                    ->from('konsep_naskah');
            });
    }

    private function receiverReviewQuery($query, $receiverTypes)
    {
        $query->where(function ($query) use ($receiverTypes) {
            $query->whereIn('ReceiverAs', $receiverTypes)
                ->orWhere('ReceiverAs', 'meneruskan')
                ->whereIn('NId', function ($draftQuery) {
                    $draftQuery->select('NId_Temp')
                        ->from('konsep_naskah')
                        ->where('Konsep', '!=', '0')
                        ->where('nosurat', '=', null);
                });
        });
    }

    private function receiverSignQuery($query, $receiverTypes)
    {
        $operator = null;
        if (in_array(CustomReceiverTypeEnum::SIGNED(), $receiverTypes)) {
            $operator = '=';
        } elseif (in_array(CustomReceiverTypeEnum::SIGN_REQUEST(), $receiverTypes)) {
            $operator = '!=';
        }

        if ($operator) {
            $query->whereIn('NId', function ($draftQuery) use ($operator) {
                $draftQuery->select('NId_Temp')
                    ->from('konsep_naskah')
                    ->where('Konsep', $operator, '0')
                    ->where('nosurat', '!=', null);
            });
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
                CustomReceiverTypeEnum::SIGNED()->value        => ['meneruskan'],
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
}
