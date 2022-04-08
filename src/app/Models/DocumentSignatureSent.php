<?php

namespace App\Models;

use App\Enums\ObjectiveTypeEnum;
use App\Enums\SignatureStatusTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DocumentSignatureSent extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd_kirim';

    protected $appends = ['urutan_parent'];

    public $timestamps = false;

    protected $enums = [
        'status' => SignatureStatusTypeEnum::class,
    ];

    protected $fillable = [
        'status',
        'next',
        'tgl_ttd',
        'is_sender_read',
        'is_receiver_read'
    ];

    public function receiver()
    {
        return $this->belongsTo(People::class, 'PeopleIDTujuan', 'PeopleId');
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'PeopleID', 'PeopleId');
    }

    public function receiverPersonalAccessTokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id', 'PeopleIDTujuan');
    }

    public function senderPersonalAccessTokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id', 'PeopleID');
    }

    public function documentSignature()
    {
        return $this->belongsTo(DocumentSignature::class, 'ttd_id', 'id');
    }

    public function objective($query, $objective)
    {
        $userId = auth()->user()->PeopleId;
        switch ($objective) {
            case ObjectiveTypeEnum::IN():
                $query->where(fn($query) => $query
                    ->where('PeopleIDTujuan', $userId)
                    ->orWhere('PeopleID', $userId)
                    ->where('status', '!=', SignatureStatusTypeEnum::WAITING()->value)
                );
                break;

            case ObjectiveTypeEnum::OUT():
                $query->where(fn($query) => $query
                    ->where('PeopleID', $userId)
                    ->orWhere('PeopleIDTujuan', $userId)
                    ->where('status', '!=', SignatureStatusTypeEnum::WAITING()->value)
                );
                break;
        }
        return $query;
    }

    public function filter($query, $filter)
    {
        $read = $filter['read'] ?? null;
        $withSender = $filter['withSender'] ?? null;
        $withReceiver = $filter['withReceiver'] ?? null;

        $withReceiverId = [];
        if ($withReceiver) {
            $withReceiverId = DocumentSignatureSent::where('PeopleIDTujuan', auth()->user()->PeopleId)
                    ->where(function ($query) use ($read) {
                        if (is_bool($read)) {
                            $query->where('is_receiver_read', $read);
                        }
                    })
                    ->pluck('id');
        }

        //show data on inbox sender if document signature is already actioned
        $withSenderId = [];
        if ($withSender) {
            $withSenderId = DocumentSignatureSent::where('PeopleID', auth()->user()->PeopleId)
                    ->where('status', '!=', SignatureStatusTypeEnum::WAITING()->value)
                    ->where(function ($query) use ($read) {
                        if (is_bool($read)) {
                            $query->where('is_sender_read', $read);
                        }
                    })
                    ->pluck('id');
        }

        $query->whereIn('id', Arr::collapse([$withReceiverId, $withSenderId]));

        $this->filterByStatus($query, $filter);
        return $query;
    }

    public function search($query, $search)
    {
        if ($search) {
            $query->whereIn('ttd_id', function ($inboxQuery) use ($search) {
                $inboxQuery->select('id')
                ->from('m_ttd')
                ->where('nama_file', 'LIKE', '%' . $search . '%');
            });
        }

        return $query;
    }

    public function filterTimeline($query, $filter)
    {
        $documentSignatureId = $filter['documentSignatureId'] ?? null;
        $sort = $filter['sort'] ?? null;
        $status = $filter['status'] ?? null;

        $query->where('ttd_id', $documentSignatureId)
            ->where('urutan', '<', $sort);

        if ($status) {
            if ($status == SignatureStatusTypeEnum::SIGNED()) {
                $query->where('status', SignatureStatusTypeEnum::SUCCESS()->value);
            }
            if ($status == SignatureStatusTypeEnum::UNSIGNED()) {
                $query->whereIn(
                    'status',
                    [
                        SignatureStatusTypeEnum::WAITING()->value,
                        SignatureStatusTypeEnum::REJECT()->value
                    ]
                );
            }
        }

        return $query;
    }

    public function getUrutanParentAttribute()
    {
        return $this->urutan - 1;
    }

    public function outboxFilter($query, $filter)
    {
        $this->filterByStatus($query, $filter);
        return $query;
    }

    private function filterByStatus($query, $filter)
    {
        $statuses = $filter['statuses'] ?? null;
        if ($statuses || $statuses == '0') {
            $arrayStatuses = explode(", ", $statuses);
            $query->whereIn('status', $arrayStatuses);
        }
    }
}
