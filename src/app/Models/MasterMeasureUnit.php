<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMeasureUnit extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'master_satuanunit';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'MeasureUnitId';
}
