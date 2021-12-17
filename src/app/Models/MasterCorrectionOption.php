<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCorrectionOption extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'master_koreksi';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'KoreksiId';
}
