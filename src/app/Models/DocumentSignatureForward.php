<?php

namespace App\Models;

use App\Enums\SignatureStatusTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hoyvoy\CrossDatabase\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentSignatureForward extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd_terusankirim';

    protected $appends = ['urutan_parent'];

    public $timestamps = false;

    protected $fillable = [
        'ttd_id',
        'catatan',
        'tgl',
        'PeopleID',
        'PeopleIDTujuan',
        'urutan',
        'status',
    ];

    public function setTglAttribute($value)
    {
        $this->attributes['tgl'] = $value->addHours(7);
    }

    public function receiver()
    {
        return $this->belongsTo(People::class, 'PeopleIDTujuan', 'PeopleId');
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'PeopleID', 'PeopleId');
    }

    public function documentSignature()
    {
        return $this->belongsTo(DocumentSignature::class, 'ttd_id', 'id');
    }
}
