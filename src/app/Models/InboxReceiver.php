<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxReceiver extends Model
{
    use HasFactory;

    protected $table = "inbox_receiver";

    protected $keyType = 'string';

    protected $primaryKey = 'NId';

    public function inboxDetail()
    {
        return $this->belongsTo(Inbox::class, 'NId', 'NId');
    }

    public function owner($query)
    {
        return $query->where('To_Id', request()->people->PeopleId);
    }

    public function filter($query, $filter)
    {
        $sources = $filter["sources"] ?? null;
        $statuses = $filter["statuses"] ?? null;
        $types = $filter["types"] ?? null;
        $urgencies = $filter["urgencies"] ?? null;

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
                    ->whereIn('JenisName', $arrayTypes);
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

        return $query;
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
}
