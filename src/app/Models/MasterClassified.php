<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterClassified extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'master_sifat';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'SifatId';
}
