<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'master_jnaskah';

    protected $keyType = 'string';

    protected $primaryKey = 'JenisId';
}
