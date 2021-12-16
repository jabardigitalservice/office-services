<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'classification';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'ClId';
}
