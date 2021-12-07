<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxDisposition extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'inbox_disposisi';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'GIR_Id';

    protected $fillable = [
        'NId',
        'GIR_Id',
        'Sifat',
        'Disposisi',
        'RoleId',
    ];

    public function getDispositionAttribute()
    {
        $splitedDispositionId = explode('|', $this->Disposisi);
        return MasterDisposition::whereIn('DisposisiId', $splitedDispositionId)->get();
    }

    public function setGirIdAttribute($value)
    {
        // GirId = peopleId + now (date in 'dmyhis' format)
        // 19 means the datetime characters numbers
        $peopleId = substr($value, 0, -19);
        $dateString = substr($value, -19);
        $date = parseDateTimeFormat($dateString, 'dmyhis');

        $this->attributes['GIR_Id'] = $peopleId . $date;
    }
}
