<?php

namespace App\Models;

use App\Enums\CustomReceiverTypeEnum;
use App\Enums\DraftObjectiveTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxReceiverCorrection extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "inbox_receiver_koreksi";

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'NId';

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
                $query->where('From_Id', '!=', $userId);
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
            $this->typeQuery($query, $urgencies);
        }

        if ($urgencies) {
            $this->urgencyQuery($query, $urgencies);
        }

        if ($receiverTypes) {
            $this->receiverQuery($query, $receiverTypes);
        }

        return $query;
    }

    protected function statusQuery($query, $statuses)
    {
        $arrayStatuses = explode(", ", $statuses);
        $query->whereIn('StatusReceive', $arrayStatuses);
    }

    protected function typeQuery($query, $types)
    {
        $arrayTypes = explode(", ", $types);
        $query->whereIn('NId', function ($draftQuery) use ($arrayTypes) {
            $draftQuery->select('NId_Temp')
                ->from('konsep_naskah')
                ->whereIn('JenisId', function ($docQuery) use ($arrayTypes) {
                    $docQuery->select('JenisId')
                        ->from('master_jnaskah')
                        ->whereIn('JenisId', $arrayTypes);
            });
        });
    }

    protected function urgencyQuery($query, $urgencies)
    {
        $arrayUrgencies = explode(", ", $urgencies);
        $query->whereIn('NId', function ($draftQuery) use ($arrayUrgencies) {
            $draftQuery->select('NId_Temp')
                ->from('konsep_naskah')
                ->whereIn('UrgensiId', function ($urgencyQuery) use ($arrayUrgencies) {
                    $urgencyQuery->select('UrgensiId')
                        ->from('master_urgensi')
                        ->whereIn('UrgensiName', $arrayUrgencies);
            });
        });
    }

    protected function receiverQuery($query, $receiverTypes)
    {
        $arrayReceiverTypes = explode(", ", $receiverTypes);
        $receiverAs = $this->getReceiverAsData($arrayReceiverTypes);

        $query->whereIn('ReceiverAs', $receiverAs)
            ->whereIn('NId', function ($draftQuery) {
                $draftQuery->select('NId_Temp')
                    ->from('konsep_naskah');
            });

        if (count($arrayReceiverTypes) == 1) {
            if (in_array(CustomReceiverTypeEnum::SIGNED(), $arrayReceiverTypes)) {
                $query->whereIn('NId', function ($draftQuery) {
                    $draftQuery->select('NId_Temp')
                    ->from('konsep_naskah')
                    ->where('Konsep', '=', '0')
                    ->where('nosurat', '!=', null);
                });
            } elseif (in_array(CustomReceiverTypeEnum::SIGN_REQUEST(), $arrayReceiverTypes)) {
                $query->whereIn('NId', function ($draftQuery) {
                    $draftQuery->select('NId_Temp')
                    ->from('konsep_naskah')
                    ->where('Konsep', '!=', '0');
                });
            }
        }
    }

    protected function getReceiverAsData($arrayReceiverTypes)
    {
        $receiverAs = [];
        foreach ($arrayReceiverTypes as $receiverTypes) {
            switch ($receiverTypes) {
                case CustomReceiverTypeEnum::CORRECTION():
                    array_push($receiverAs, 'koreksi');
                    break;

                case CustomReceiverTypeEnum::NUMBERING():
                    array_push($receiverAs, 'Meminta Nomber Surat');
                    break;

                case CustomReceiverTypeEnum::SIGN_REQUEST():
                case CustomReceiverTypeEnum::SIGNED():
                    array_push($receiverAs, 'meneruskan');
                    break;

                default:
                    $this->getReceiverAsReviewData($receiverAs);
                    break;
            }
        }
        return $receiverAs;
    }

    protected function getReceiverAsReviewData($receiverAs)
    {
        array_push($receiverAs,
            'to_draft_keluar',
            'to_draft_notadinas',
            'to_draft_edaran',
            'to_draft_sprint',
            'to_draft_instruksi_gub',
            'to_draft_sket',
            'to_draft_super_tugas',
            'to_draft_pengumuman',
            'to_draft_surat_izin',
            'to_draft_rekomendasi',
            'to_koreksi'
        );
    }
}
