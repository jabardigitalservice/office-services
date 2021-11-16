<?php

namespace App\Models;

use App\Enums\SignatureStatusTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hoyvoy\CrossDatabase\Eloquent\Model;
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
        'tgl_ttd'
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

    public function documentSignatureSentRead()
    {
        return $this->belongsTo(DocumentSignatureSentRead::class, 'id', 'document_signature_sent_id');
    }

    public function documentSignature()
    {
        return $this->belongsTo(DocumentSignature::class, 'ttd_id', 'id');
    }

    public function filter($query, $filter)
    {
        $statuses = $filter['statuses'] ?? null;
        $read = $filter['read'] ?? null;
        $unread = $filter['unread'] ?? null;
        $withSender = $filter['withSender'] ?? null;
        $withReceiver = $filter['withReceiver'] ?? null;
        $readId = [];

        $withReceiverId = [];
        if ($withReceiver) {
            $withReceiverId = DB::connection('sikdweb')->table('m_ttd_kirim')
            ->select('id')
            ->where('PeopleIDTujuan', auth()->user()->PeopleId)
            ->pluck('id');
        }

        //get data by sender if data has success/reject status value
        $withSenderId = [];
        if ($withSender) {
            $withSenderId = DB::connection('sikdweb')->table('m_ttd_kirim')
            ->select('id')
            ->where('PeopleID', auth()->user()->PeopleId)
            ->where('status', '!=', SignatureStatusTypeEnum::WAITING()->value)
            ->pluck('id');
        }

        $documentSignatureSentIds = Arr::collapse([$withReceiverId, $withSenderId]);

        if ($read || $unread) {
            $readId = DB::connection('mysql')->table('document_signature_sent_reads')
            ->select('document_signature_sent_id')
            ->pluck('document_signature_sent_id');
        }

        if ($read && !$unread) {
            $documentSignatureSentIds = array_intersect($documentSignatureSentIds, $readId->toArray());
        }

        $query->whereIn('id', $documentSignatureSentIds);

        if (!$read && $unread) {
            $query->whereNotIn('id', $readId);
        }

        if ($statuses  || $statuses == '0') {
            $arrayStatuses = explode(", ", $statuses);
            $query->whereIn('status', $arrayStatuses);
        }

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

        $query->where('ttd_id', $documentSignatureId)
            ->where('urutan', '<', $sort)
            ->where('status', SignatureStatusTypeEnum::SUCCESS()->value);

        return $query;
    }

    public function getUrutanParentAttribute()
    {
        return $this->urutan - 1;
    }
}
