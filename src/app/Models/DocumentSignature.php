<?php

namespace App\Models;

use App\Enums\SignatureStatusTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd';

    public $timestamps = false;

    public function getUrlAttribute()
    {
        $path = config('sikd.base_path_file');
        $file = $path . 'ttd/sudah_ttd/' . $this->file;
        $headers = @get_headers($file);
        if ($headers && strpos($headers[3], 'application/pdf')) {
            $file = $file;
        } else {
            $file = $path . 'ttd/blm_ttd/' . $this->file;
        }
        return $file;
    }

    public function documentSignatureSents()
    {
        return $this->hasMany(DocumentSignatureSent::class, 'ttd_id', 'id');
    }

    public function inboxFile()
    {
        return $this->belongsTo(InboxFile::class, 'file', 'FileName_real');
    }
}
