<?php

namespace App\Models;

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
}
