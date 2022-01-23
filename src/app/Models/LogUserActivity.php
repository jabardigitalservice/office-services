<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogUserActivity extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
}
