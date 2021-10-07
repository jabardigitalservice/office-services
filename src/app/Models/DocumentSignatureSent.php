<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hoyvoy\CrossDatabase\Eloquent\Model;

class DocumentSignatureSent extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd_kirim';

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

        if ($read != true && !$unread != true) {
            if ($read) {
                $query->whereHas('documentSignatureSentRead');
            }

            if ($unread) {
                $query->whereDoesntHave('documentSignatureSentRead');
            }
        }

        return $query;
    }
}
