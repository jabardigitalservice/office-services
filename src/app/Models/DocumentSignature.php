<?php

namespace App\Models;

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
        return $path . $this->folder_url . $this->file;
    }

    public function getFolderUrlAttribute()
    {
        $folder = 'ttd/sudah_ttd/';
        if ($this->status == 0) {
            $folder = 'ttd/blm_ttd/';
        }

        return $folder;
    }
}
