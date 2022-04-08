<?php

namespace App\Models;

use App\Enums\SignatureStatusTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentSignatureForward extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'm_ttd_terusankirim';

    protected $appends = ['urutan_parent'];

    public $timestamps = false;

    protected $fillable = [
        'ttd_id',
        'catatan',
        'tgl',
        'PeopleID',
        'PeopleIDTujuan',
        'urutan',
        'status',
        'is_read',
    ];

    public function setTglAttribute($value)
    {
        $this->attributes['tgl'] = $value->addHours(7);
    }

    public function receiver()
    {
        return $this->belongsTo(People::class, 'PeopleIDTujuan', 'PeopleId');
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'PeopleID', 'PeopleId');
    }

    public function documentSignature()
    {
        return $this->belongsTo(DocumentSignature::class, 'ttd_id', 'id');
    }

    /**
     * Search the list by its file name
     *
     * @param Object $query
     * @param Array $search
     *
     * @return Object
     */
    public function search($query, $search)
    {
        $query->whereIn(
            'ttd_id',
            fn($query) => $query->select('id')
                ->from('m_ttd')
                ->where('nama_file', 'LIKE', '%' . $search . '%')
        );

        return $query;
    }

    /**
     * Filtering the list
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Object
     */
    public function filter($query, $filter)
    {
        $this->filterByReadStatus($query, $filter);
        $this->filterByDistributionStatus($query, $filter);
        return $query;
    }

     /**
     * Filtering list by read status
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    protected function filterByReadStatus($query, $filter)
    {
        $isRead = $filter['isRead'] ?? null;
        if ($isRead || $isRead == '0') {
            $arrayIsRead = explode(', ', $isRead);
            $query->whereIn('is_read', $arrayIsRead);
        }
    }

    /**
     * Filtering list by distribution status
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    protected function filterByDistributionStatus($query, $filter)
    {
        $isDistributed = $filter['isDistributed'] ?? null;
        if ($isDistributed || $isDistributed == '0') {
            $arrayIsDistributed = explode(', ', $isDistributed);
            $query->whereIn('status', $arrayIsDistributed);
        }
    }
}
