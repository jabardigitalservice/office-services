<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "konsep_naskah";

    protected $keyType = 'string';

    protected $primaryKey = 'NId_Temp';

    public function sender()
    {
        return $this->belongsTo(People::class, 'CreateBy', 'PeopleId');
    }

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

    public function approver()
    {
        return $this->belongsTo(People::class, 'Approve_People3', 'PeopleId');
    }

    public function draftType()
    {
        return $this->belongsTo(MasterDraftType::class, 'JenisId', 'JenisId');
    }

    public function classified()
    {
        return $this->belongsTo(MasterClassified::class, 'SifatId', 'SifatId');
    }

    public function measureUnit()
    {
        return $this->belongsTo(MasterMeasureUnit::class, 'MeasureUnitId', 'MeasureUnitId');
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class, 'ClId', 'ClId');
    }
}
