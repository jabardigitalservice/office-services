<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxDisposition extends Model
{
    use HasFactory;

    protected $table = "inbox_disposisi";

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'NId';

    public function getDispositionAttribute()
    {
        $splitedDispositionId = explode('|', $this->Disposisi);
        return MasterDisposition::whereIn('DisposisiId', $splitedDispositionId)->get();
    }
}
