<?php

namespace App\Models;

use App\Enums\InboxReceiverScopeType;
use App\Enums\PeopleGroupTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hoyvoy\CrossDatabase\Eloquent\Model;

class InboxReceiver extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = "inbox_receiver";

    public $timestamps = false;

    protected $appends = ['purpose', 'inbox_disposition'];

    protected $fillable = [
        'NId',
        'NKey',
        'GIR_Id',
        'From_Id',
        'RoleId_From',
        'To_Id',
        'RoleId_To',
        'ReceiverAs',
        'Msg',
        'StatusReceive',
        'ReceiveDate',
        'To_Id_Desc',
        'Status'
    ];

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }

    public function history($query, $NId)
    {
        return $query->where('NId', $NId)
            ->where(function ($query) {
                $query->whereIn('GIR_Id', function ($query) {
                    $query->select('GIR_Id')
                        ->from('inbox_receiver')
                        ->where('RoleId_To', 'like', auth()->user()->PrimaryRoleId . '%');
                })
                ->orWhere('RoleId_From', 'like', auth()->user()->PrimaryRoleId . '%');
            });
    }

    public function sender()
    {
        return $this->belongsTo(People::class, 'From_Id', 'PeopleId');
    }

    public function receiver()
    {
        return $this->belongsTo(People::class, 'To_Id', 'PeopleId');
    }

    public function senderByRoleId()
    {
        return $this->belongsTo(People::class, 'RoleId_From', 'PrimaryRoleId');
    }

    public function receiverByRoleId()
    {
        if (auth()->user()->GroupId == PeopleGroupTypeEnum::TU()->value) {
            return $this->belongsTo(People::class, 'RoleId_To', 'PrimaryRoleId');
        }
        return $this->receiver();
    }

    public function personalAccessTokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id', 'To_Id');
    }

    public function inboxDisposition()
    {
        return $this->belongsTo(InboxDisposition::class, 'GIR_Id', 'GIR_Id');
    }

    public function filter($query, $filter)
    {
        $sources = $filter["sources"] ?? null;
        $statuses = $filter["statuses"] ?? null;
        $types = $filter["types"] ?? null;
        $urgencies = $filter["urgencies"] ?? null;
        $forwarded = $filter["forwarded"] ?? null;
        $folder = $filter["folder"] ?? null;
        $receiverTypes = $filter["receiverTypes"] ?? null;
        $scope = $filter["scope"] ?? null;

        if ($statuses) {
            $arrayStatuses = explode(", ", $statuses);
            $query->whereIn('StatusReceive', $arrayStatuses);
        }

        if ($sources) {
            $arraySources = explode(", ", $sources);
            $query->whereIn('NId', function ($inboxQuery) use ($arraySources) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('Pengirim', $arraySources);
            });
        }

        if ($types) {
            $arrayTypes = explode(", ", $types);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayTypes) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('JenisId', function ($docQuery) use ($arrayTypes) {
                    $docQuery->select('JenisId')
                    ->from('master_jnaskah')
                    ->whereIn('JenisId', $arrayTypes);
                });
            });
        }

        if ($urgencies) {
            $arrayUrgencies = explode(", ", $urgencies);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayUrgencies) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('UrgensiId', function ($urgencyQuery) use ($arrayUrgencies) {
                    $urgencyQuery->select('UrgensiId')
                    ->from('master_urgensi')
                    ->whereIn('UrgensiName', $arrayUrgencies);
                });
            });
        }

        if ($folder) {
            $arrayFolders = explode(", ", $folder);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayFolders) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('NTipe', $arrayFolders);
            });
            $query->where('ReceiverAs', 'to');
        }

        if ($forwarded || $forwarded == '0') {
            $arrayForwarded = explode(", ", $forwarded);
            $query->whereIn('Status', $arrayForwarded);
        }

        if ($receiverTypes) {
            $arrayReceiverTypes = explode(", ", $receiverTypes);
            $query->whereIn('ReceiverAs', $arrayReceiverTypes);
        }

        if ($scope) {
            $departmentId = $this->generateDeptId(auth()->user()->PrimaryRoleId);
            $comparison = '';
            switch ($scope) {
                case InboxReceiverScopeType::REGIONAL():
                    $comparison = 'NOT LIKE';
                    break;

                case InboxReceiverScopeType::INTERNAL():
                    $comparison = 'LIKE';
                    break;
            }
            $query->where('RoleId_From', $comparison, $departmentId . '%');
        }

        return $query;
    }

    private function generateDeptId($roleId)
    {
        // If the user is not uk.setda
        if ($roleId != 'uk.1.1.1.1.1') {
            $arrayRoleId = explode(".", $roleId);
            $arrayDepartmentId = array_slice($arrayRoleId, 0, 3);
            $departmentId = join(".", $arrayDepartmentId);
            return $departmentId;
        }
        return $roleId;
    }

    public function search($query, $search)
    {
        $query->whereIn('NId', function ($inboxQuery) use ($search) {
            $inboxQuery->select('NId')
            ->from('inbox')
            ->where('Hal', 'LIKE', '%' . $search . '%');
        });

        return $query;
    }

    public function getPurposeAttribute()
    {
        return InboxReceiver::where('NId', $this->NId)
                        ->where('GIR_Id', $this->GIR_Id)
                        ->get();
    }

    public function getInboxDispositionAttribute()
    {
        return InboxDisposition::where('NId', $this->NId)
                        ->where('GIR_Id', $this->GIR_Id)
                        ->get();
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

    public function setReceiveDateAttribute($value)
    {
        $this->attributes['ReceiveDate'] = $value->copy()->addHours(7);
    }

    public function getReceiverAsLabelAttribute()
    {
        $label = match ($this->ReceiverAs) {
            'to'                    => 'Naskah Masuk',
            'to_undangan'           => 'Undangan',
            'to_sprint'             => 'Perintah',
            'to_notadinas'          => 'Nota Dinas',
            'to_reply'              => 'Naskah Dinas',
            'to_usul'               => 'Jawaban Nota Dinas',
            'to_forward'            => 'Teruskan',
            'cc1'                   => 'Disposisi',
            'to_keluar'             => 'Surat Dinas Keluar',
            'to_nadin'              => 'Naskah Dinas Lainnya',
            'to_konsep'             => 'Konsep Naskah',
            'to_memo'               => 'Memo',
            'to_draft_notadinas'    => 'Konsep Nota Dinas',
            'to_draft_sprint'       => 'Konsep Surat Perintah',
            'to_draft_undangan'     => 'Konsep Undangan',
            'to_draft_keluar'       => 'Konsep surat Dinas',
            'to_draft_sket'         => 'Konsep surat Keterangan',
            'to_draft_pengumuman'   => 'Konsep Pengumuman',
            'to_draft_rekomendasi'  => 'Konsep Surat Rekomendasi',
            default                 => 'Konsep Naskah Dinas Lainnya'
        };

        return $label;
    }
}
