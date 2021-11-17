<?php

namespace App\Models;

use App\Enums\DraftProcessTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "konsep_naskah";

    protected $keyType = 'string';

    protected $primaryKey = 'NId_Temp';

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'JenisId', 'JenisId');
    }

    public function urgency()
    {
        return $this->belongsTo(DocumentUrgency::class, 'UrgensiId', 'UrgensiId');
    }

    public function createdBy()
    {
        return $this->belongsTo(People::class, 'CreateBy', 'PeopleId');
    }

    public function reviewer()
    {
        return $this->belongsTo(People::class, 'Approve_People', 'PeopleId');
    }

    public function search($query, $search)
    {
        $query->where('Hal', 'LIKE', '%' . $search . '%');
        return $query;
    }

    public function filter($query, $filter)
    {
        $process = $filter['process'] ?? null;
        if ($process && $process == DraftProcessTypeEnum::REVIEW()) {
            $query->where('Number', 0)
                ->where('nosurat', null);
        }

        return $query;
    }
}
