<?php

namespace App\Models;

use App\Enums\PeopleGroupTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hoyvoy\CrossDatabase\Eloquent\Model;

class InboxReceiver extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "inbox_receiver";

    public $timestamps = false;

    protected $appends = ['purpose', 'inbox_disposition'];

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
        'Status'
    ];

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }

    public function history($query, $NId)
    {
        return $query->where('NId', $NId)
                    ->where(function($query) use ($NId) {
                        $query->where('NId', $NId);
                        $query->orwhere('RoleId_To', 'like', auth()->user()->PrimaryRoleId . '%');
                        $query->orWhere('RoleId_From', 'like', auth()->user()->PrimaryRoleId . '%');
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

    public function senderByRoleId()
    {
        return $this->belongsTo(People::class, 'RoleId_From', 'PrimaryRoleId');
    }

    public function receiverByRoleId()
    {
        if (auth()->user()->GroupId == PeopleGroupTypeEnum::TU()->value) {
            return $this->belongsTo(People::class, 'RoleId_To', 'PrimaryRoleId');
        }
        return $this->receiver();
    }

    public function personalAccessTokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id', 'To_Id');
    }

    public function filter($query, $filter)
    {
        $sources = $filter["sources"] ?? null;
        $statuses = $filter["statuses"] ?? null;
        $types = $filter["types"] ?? null;
        $urgencies = $filter["urgencies"] ?? null;
        $forwarded = $filter["forwarded"] ?? null;
        $folder = $filter["folder"] ?? null;
        $receiverTypes = $filter["receiverTypes"] ?? null;

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
                    ->whereIn('JenisId', $arrayTypes);
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

        if ($folder) {
            $arrayFolders = explode(", ", $folder);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayFolders) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('NTipe', $arrayFolders);
            });
            $query->where('ReceiverAs', 'to');
        }

        if ($forwarded || $forwarded == '0') {
            $arrayForwarded = explode(", ", $forwarded);
            $query->whereIn('Status', $arrayForwarded);
        }

        if ($receiverTypes) {
            $arrayReceiverTypes = explode(", ", $receiverTypes);
            $query->whereIn('ReceiverAs', $arrayReceiverTypes);
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

    public function setGirIdAttribute($value)
    {
        // GirId = peopleId + now (date in 'dmyhis' format)
        // 19 means the datetime characters numbers
        $peopleId = substr($value, 0, -19);
        $dateString = substr($value, -19);
        $date = parseDateTimeFormat($dateString, 'dmyhis');

        $this->attributes['GIR_Id'] = $peopleId . $date;
    }

    public function setReceiveDateAttribute($value)
    {
        $this->attributes['ReceiveDate'] = $value->addHours(7);
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
