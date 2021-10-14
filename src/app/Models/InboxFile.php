<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxFile extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "inbox_files";

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'Id_dokumen';

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }
}
