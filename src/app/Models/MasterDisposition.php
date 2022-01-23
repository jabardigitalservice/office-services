<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDisposition extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    protected $table = 'master_disposisi';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'DisposisiId';

    public function groupPosition()
    {
        return $this->belongsTo(GroupPosition::class, 'gjabatanId', 'gjabatanId');
    }

    public function byGroupPosition()
    {
        return $this->belongsToMany(People::class, 'role', 'gjabatanId', 'RoleId', 'gjabatanId', 'PrimaryRoleId');
    }
}
