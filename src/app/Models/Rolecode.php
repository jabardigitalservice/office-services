<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rolecode extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = "rolecode";

    protected $primaryKey = 'rolecode_id';
}
