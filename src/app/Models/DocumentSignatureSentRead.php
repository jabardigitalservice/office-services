<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignatureSentRead extends Model
{
    use HasFactory;

    use \Awobaz\Compoships\Compoships;

    protected $connection = 'mysql';
}
