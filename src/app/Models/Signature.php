<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'ttd';

    protected $primaryKey = 'TtdId';

    public $timestamps = false;

    public function setTglProsesAttribute($value)
    {
        $this->attributes['TglProses'] = $value->copy()->setTimezone(config('sikd.timezone_server'));
    }
}
