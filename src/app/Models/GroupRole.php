<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupRole extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    public $timestamps = false;

    protected $table = 'master_grole';

    protected $keyType = 'string';

    protected $primaryKey = 'GRoleId';
}
