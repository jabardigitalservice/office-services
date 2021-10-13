<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hoyvoy\CrossDatabase\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentSignatureSent extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd_kirim';

    protected $appends = ['urutan_parent'];

    public $timestamps = false;

    public function receiver()
    {
        return $this->belongsTo(People::class, 'PeopleIDTujuan', 'PeopleId');
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'PeopleID', 'PeopleId');
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
        $statuses = $filter["statuses"] ?? null;
        $read = $filter["read"] ?? null;
        $unread = $filter["unread"] ?? null;

        if ($statuses  || $statuses == '0') {
            $arrayStatuses = explode(", ", $statuses);
            $query->whereIn('status', $arrayStatuses);
        }

        if ($read || $unread) {
            $readedId = DB::connection('mysql')->table('document_signature_sent_reads')
            ->select('document_signature_sent_id')
            ->pluck('document_signature_sent_id');
        }

        if ($read && !$unread) {
            $query->whereIn('id', $readedId);
        }

        if (!$read && $unread) {
            $readedId = DB::connection('mysql')->table('document_signature_sent_reads')
            ->select('document_signature_sent_id')
            ->pluck('document_signature_sent_id');
            $query->whereNotIn('id', $readedId);
        }

        return $query;
    }

    public function search($query, $search)
    {
        $query->whereIn('ttd_id', function ($inboxQuery) use ($search) {
            $inboxQuery->select('id')
            ->from('m_ttd')
            ->where('nama_file', 'LIKE', '%' . $search . '%');
        });

        return $query;
    }

    public function getUrutanParentAttribute()
    {
        return $this->urutan - 1;
    }
}
