<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupPosition extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    public $timestamps = false;

    protected $table = "master_gjabatan";

    protected $keyType = 'string';

    protected $primaryKey = 'gjabatanId';
}
