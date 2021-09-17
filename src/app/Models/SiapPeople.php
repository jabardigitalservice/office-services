<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiapPeople extends Model
{
    use HasFactory;

    protected $table = "siap_pegawai";

    protected $keyType = 'string';

    protected $primaryKey = 'peg_nip';
}
