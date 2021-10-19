<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassphraseSession extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'sesi_pasphrase';

    public $timestamps = false;

    public function setJamAksesAttribute($value)
    {
        $this->attributes['jam_akses'] = $value->setTimezone(config('sikd.timezone_server'));
    }
}
