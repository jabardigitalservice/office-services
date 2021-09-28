<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxFile extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "inbox_files";

    public function getDocumentFileAttribute()
    {
        return config('sikd.base_path_file') . $this->NFileDir . '/' . $this->file->FileName_fake;
    }
}
