<?php

namespace App\Models;

use App\Enums\InboxTypeEnum;
use App\Enums\ListTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Http\Traits\InboxFilterTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxReceiver extends Model
{
    use HasFactory;
    use InboxFilterTrait;

    protected $connection = 'sikdweb';

    protected $table = 'inbox_receiver';

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
        'Status',
        'TindakLanjut',
        'action_label'
    ];

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }

    public function history($query, $filter)
    {
        return $query->where('NId', $filter['inboxId'])
            ->where(function ($query) use ($filter) {
                if ($filter['withAuthCheck']) {
                    $query->whereIn('GIR_Id', function ($query) {
                        $query->select('GIR_Id')
                            ->from('inbox_receiver')
                            ->where('RoleId_To', 'like', auth()->user()->PrimaryRoleId . '%');
                    })
                    ->orWhere('RoleId_From', 'like', auth()->user()->PrimaryRoleId . '%');
                }
            })
            ->where(function ($query) use ($filter) {
                $status = $filter['status'] ?? null;
                $excludeStatus = $filter['excludeStatus'] ?? null;
                if ($status) {
                    $status = explode(', ', $status);
                    $query->whereIn('ReceiverAs', $status);
                }
                if ($excludeStatus) {
                    $excludeStatus = explode(', ', $excludeStatus);
                    $query->whereNotIn('ReceiverAs', $excludeStatus);
                }
            });
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

    public function inboxDisposition()
    {
        return $this->belongsTo(InboxDisposition::class, 'GIR_Id', 'GIR_Id');
    }

    public function filter($query, $filter)
    {
        $this->filterByResource($query, $filter);
        $this->filterByStatus($query, $filter);
        $this->filterByType($query, $filter, ListTypeEnum::INBOX_LIST());
        $this->filterByUrgency($query, $filter, ListTypeEnum::INBOX_LIST());
        $this->filterByFolder($query, $filter);
        $this->filterByForwardStatus($query, $filter);
        $this->filterByReceiverTypes($query, $filter);
        $this->filterByFollowedUpStatus($query, $filter);
        $this->filterByActionLabel($query, $filter);
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
        $this->attributes['ReceiveDate'] = $value->copy()->addHours(7);
    }

    public function getReceiverAsLabelAttribute()
    {
        $nonDispositionLabel = '';
        if (
            $this->inboxDetail->NTipe == InboxTypeEnum::INBOX()->value ||
            $this->inboxDetail->NTipe == InboxTypeEnum::OUTBOXNOTADINAS()->value
        ) {
            $nonDispositionLabel = ' Non Disposisi';
        }

        $label = match ($this->ReceiverAs) {
            'to'                    => 'Naskah Masuk' . $nonDispositionLabel,
            'to_undangan'           => 'Undangan',
            'to_sprint'             => 'Perintah',
            'to_notadinas'          => 'Nota Dinas',
            'to_reply'              => 'Naskah Dinas',
            'to_usul'               => 'Jawaban Nota Dinas',
            'to_forward'            => 'Teruskan',
            'cc1'                   => 'Disposisi',
            'bcc'                   => 'Tembusan',
            'to_keluar'             => 'Surat Dinas Keluar',
            'to_nadin'              => 'Naskah Dinas Lainnya',
            'to_konsep'             => 'Konsep Naskah',
            'to_memo'               => 'Memo',
            'to_draft_notadinas'    => 'Konsep Nota Dinas',
            'to_draft_sprint'       => 'Konsep Surat Perintah',
            'to_draft_undangan'     => 'Konsep Undangan',
            'to_draft_keluar'       => 'Konsep surat Dinas',
            'to_draft_sket'         => 'Konsep surat Keterangan',
            'to_draft_pengumuman'   => 'Konsep Pengumuman',
            'to_draft_rekomendasi'  => 'Konsep Surat Rekomendasi',
            default                 => 'Konsep Naskah Dinas Lainnya'
        };

        return $label;
    }
}
