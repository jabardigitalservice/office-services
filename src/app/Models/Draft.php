<?php

namespace App\Models;

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

    protected $fillable = [
        'RoleId_From',
        'Approve_People',
        'Nama_ttd_konsep'
    ];

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
        if ($this->inboxFile) {
            $file = config('sikd.base_path_file_letter') . $this->inboxFile->FileName_fake;
        }

        return $file;
    }

    public function getAboutAttribute()
    {
        return str_replace('&nbsp;', ' ', strip_tags($this->Hal));
    }

    public function getDocumentFileNameAttribute()
    {
        $label = match ($this->Ket) {
            'outboxnotadinas'       => 'Nota Dinas',
            'outboxsprint'          => 'Surat Perintah Perangkat Daerah',
            'outboxsprintgub'       => 'Surat Perintah Gubernur',
            'outboxundangan'        => 'Surat Undangan',
            'outboxedaran'          => 'Surat Edaran',
            'outboxinstruksigub'    => 'Surat Instruksi Gubernur',
            'outboxsupertugas'      => 'Surat Pernyataan Melaksanakan Tugas',
            'outboxkeluar'          => 'Surat Dinas',
            'outboxsket'            => 'Surat Keterangan',
            'outboxpengumuman'      => 'Pengumuman',
            'outboxsuratizin'       => 'Surat Izin',
            'outboxrekomendasi'     => 'Surat Rekomendasi',
            default                 => 'Nadin Lain',
        };

        return $label;
    }

    public function getDocumentTemplateNameAttribute()
    {
        $label = match ($this->Ket) {
            'outboxnotadinas'       => 'pdf.nota_dinas',
            'outboxsprint'          => 'pdf.surat_perintah',
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
