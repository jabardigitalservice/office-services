<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxReceiver extends Model
{
    use HasFactory;

    protected $table = "inbox_receiver";

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'NId';

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }

    public function owner($query)
    {
        return $query->where('To_Id', request()->people->PeopleId);
    }

    public function history($query)
    {
        return $query->where(function ($query) {
            $query->where('RoleId_To', 'like', request()->people->PrimaryRoleId . '%');
            $query->orWhere('RoleId_From', 'like', request()->people->PrimaryRoleId . '%');
        })->groupBy('GIR_Id');
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'From_Id', 'PeopleId');
    }

    public function receiver()
    {
        return $this->belongsTo(People::class, 'To_Id', 'PeopleId');
    }

    public function filter($query, $filter)
    {
        $sources = $filter["sources"] ?? null;
        $statuses = $filter["statuses"] ?? null;
        $types = $filter["types"] ?? null;
        $urgencies = $filter["urgencies"] ?? null;
        $forwarded = $filter["forwarded"] ?? null;

        if ($statuses) {
            $arrayStatuses = explode(", ", $statuses);
            $query->whereIn('StatusReceive', $arrayStatuses);
        }

        if ($sources) {
            $arraySources = explode(", ", $sources);
            $query->whereIn('NId', function ($inboxQuery) use ($arraySources) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('Pengirim', $arraySources);
            });
        }

        if ($types) {
            $arrayTypes = explode(", ", $types);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayTypes) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('JenisId', function ($docQuery) use ($arrayTypes) {
                    $docQuery->select('JenisId')
                    ->from('master_jnaskah')
                    ->whereIn('JenisName', $arrayTypes);
                });
            });
        }

        if ($urgencies) {
            $arrayUrgencies = explode(", ", $urgencies);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayUrgencies) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('UrgensiId', function ($urgencyQuery) use ($arrayUrgencies) {
                    $urgencyQuery->select('UrgensiId')
                    ->from('master_urgensi')
                    ->whereIn('UrgensiName', $arrayUrgencies);
                });
            });
        }

        if ($forwarded || $forwarded == '0') {
            $arrayForwarded = explode(", ", $forwarded);
            $query->whereIn('Status', $arrayForwarded);
        }

        return $query;
    }

    public function search($query, $search)
    {
        $query->whereIn('NId', function ($inboxQuery) use ($search) {
            $inboxQuery->select('NId')
            ->from('inbox')
            ->where('Hal', 'LIKE', '%' . $search . '%');
        });

        return $query;
    }

    public function getPurposeAttribute()
    {
        return InboxReceiver::where('NId', $this->NId)
                        ->where('GIR_Id', $this->GIR_Id)
                        ->get();
    }

    public function getInboxDispositionAttribute()
    {
        return InboxDisposition::where('NId', $this->NId)
                        ->where('GIR_Id', $this->GIR_Id)
                        ->get();
    }

    public function getReceiverAsLabelAttribute()
    {
        switch ($this->ReceiverAs) {
            case 'to':
                return "Naskah Masuk";
                break;

            case 'to_undangan':
                return "Undangan";
                break;

            case 'to_sprint':
                return "Surat Perintah";
                break;

            case 'to_notadinas':
                return "Nota Dinas";
                break;

            case 'to_reply':
                return "Nota Dinas";
                break;

            case 'to_usul':
                return "Jawaban Nota Dinas";
                break;

            case 'to_forward':
                return "Teruskan";
                break;

            case 'cc1':
                return "Disposisi";
                break;

            case 'to_keluar':
                return "Surat Dinas Keluar";
                break;

            case 'to_nadin':
                return "Naskah Dinas Lainnya";
                break;

            case 'to_konsep':
                return "Konsep Naskah";
                break;

            case 'to_memo':
                return "Memo";
                break;

            case 'to_draft_notadinas':
                return "Konsep Nota Dinas";
                break;

            case 'to_draft_sprint':
                return "Konsep Surat Perintah";
                break;

            case 'to_draft_undangan':
                return "Konsep Undangan";
                break;

            case 'to_draft_keluar':
                return "Konsep surat Dinas";
                break;

            case 'to_draft_sket':
                return "Konsep surat Keterangan";
                break;

            case 'to_draft_pengumuman':
                return "Konsep Pengumuman";
                break;

            case 'to_draft_rekomendasi':
                return "Konsep Surat Rekomendasi";
                break;

            default:
                return "Konsep Naskah Dinas Lainnya";
                break;
        }
    }
}
