<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentUrgency extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'master_urgensi';

    protected $keyType = 'string';

    protected $primaryKey = 'UrgensiId';
}
