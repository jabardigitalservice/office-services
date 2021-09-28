<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbox extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "inbox";

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
}
