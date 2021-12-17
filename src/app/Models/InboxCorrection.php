<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxCorrection extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'inbox_koreksi';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'NId';

    public function setGirIdAttribute($value)
    {
        $peopleId = substr($value, 0, -19);
        $dateString = substr($value, -19);
        $date = parseDateTimeFormat($dateString, 'dmyhis');

        $this->attributes['GIR_Id'] = $peopleId . $date;
    }

    public function setKoreksiAttribute($value)
    {
        $correctionOptions = str_replace(', ', '|', $value);

        $this->attributes['Koreksi'] = $correctionOptions;
    }
}
