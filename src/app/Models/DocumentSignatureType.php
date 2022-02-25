<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignatureType extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd_types';

    public $timestamps = false;
}
