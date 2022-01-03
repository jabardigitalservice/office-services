<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbox extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'inbox';

    protected $keyType = 'string';

    protected $primaryKey = 'NId';

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'JenisId', 'JenisId');
    }

    public function urgency()
    {
        return $this->belongsTo(DocumentUrgency::class, 'UrgensiId', 'UrgensiId');
    }

    public function documentFile()
    {
        return $this->belongsTo(InboxFile::class, 'NId', 'NId');
    }

    public function getDocumentBaseUrlAttribute()
    {
        return config('sikd.base_path_file');
    }

    public function createdBy()
    {
        return $this->belongsTo(People::class, 'CreatedBy', 'PeopleId');
    }

    public function setNTglRegAttribute($value)
    {
        $this->attributes['NTglReg'] = $value->copy()->addHours(7);
    }
}
