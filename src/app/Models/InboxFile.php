<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxFile extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'inbox_files';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'Id_dokumen';

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }

    public function inboxReceivers()
    {
        return $this->hasMany(InboxReceiver::class, 'GIR_Id', 'GIR_Id');
    }

    public function find($query, $id)
    {
        $query->where('Id_dokumen', $id)
            ->orWhere('NId', $id)
            ->where('Id_dokumen', '<>', '');

        return $query;
    }
}
