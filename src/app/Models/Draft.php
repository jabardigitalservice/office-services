<?php

namespace App\Models;

use App\Enums\DraftConceptStatusTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Draft extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'konsep_naskah';

    protected $keyType = 'string';

    protected $primaryKey = 'NId_Temp';

    public $timestamps = false;

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

    public function inboxFile()
    {
        return $this->belongsTo(InboxFile::class, 'NId_Temp', 'NId');
    }

    public function getDraftFileAttribute()
    {
        $file = URL::to('/api/v1/draft/' . $this->NId_Temp);
        if ($this->Konsep == DraftConceptStatusTypeEnum::APPROVED()->value) {
            $file = config('sikd.base_path_file_letter') . $this->inboxFile->FileName_fake;
        }

        return $file;
    }

    public function getDocumentFileNameAttribute()
    {
        $label = match ($this->Ket) {
            'outboxnotadinas'       => 'nota_dinas-' . $this->NId_Temp . '.pdf',
            'outboxsprint'          => 'sprint-' . $this->NId_Temp . '.pdf',
            'outboxsprintgub'       => 'sprintgub-' . $this->NId_Temp . '.pdf',
            'outboxundangan'        => 'undangan-' . $this->NId_Temp . '.pdf',
            'outboxedaran'          => 'surat_edaran-' . $this->NId_Temp . '.pdf',
            'outboxinstruksigub'    => 'surat_instruksi-' . $this->NId_Temp . '.pdf',
            'outboxsupertugas'      => 'surat_supertugas-' . $this->NId_Temp . '.pdf',
            'outboxkeluar'          => 'surat_dinas-' . $this->NId_Temp . '.pdf',
            'outboxsket'            => 'surat_keterangan-' . $this->NId_Temp . '.pdf',
            'outboxpengumuman'      => 'pengumuman-' . $this->NId_Temp . '.pdf',
            'outboxsuratizin'       => 'surat_izin-' . $this->NId_Temp . '.pdf',
            'outboxrekomendasi'     => 'rekomendasi-' . $this->NId_Temp . '.pdf',
            default                 => 'nadin_lain-' . $this->NId_Temp . '.pdf',
        };

        return $label;
    }

    public function getDocumentTemplateNameAttribute()
    {
        $label = match ($this->Ket) {
            'outboxnotadinas'       => 'pdf.nota_dinas',
            'outboxsprint'          => 'pdf.sprint',
            'outboxsprintgub'       => 'pdf.sprintgub',
            'outboxundangan'        => 'pdf.undangan',
            'outboxedaran'          => 'pdf.surat_edaran',
            'outboxinstruksigub'    => 'pdf.surat_instruksi',
            'outboxsupertugas'      => 'pdf.surat_supertugas',
            'outboxkeluar'          => 'pdf.surat_dinas',
            'outboxsket'            => 'pdf.surat_keterangan',
            'outboxpengumuman'      => 'pdf.pengumuman',
            'outboxsuratizin'       => 'pdf.surat_izin',
            'outboxrekomendasi'     => 'pdf.rekomendasi',
            default                 => 'pdf.nadin_lain',
        };

        return $label;
    }
}
