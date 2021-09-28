<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $connection = 'sikdweb';

    public $timestamps = false;

    protected $table = "role";

    protected $keyType = 'string';

    protected $primaryKey = 'RoleCode';

    public function rolecode()
    {
        return $this->belongsTo(Rolecode::class, 'RoleCode', 'rolecode_id');
    }

    public function groupPosition()
    {
        return $this->belongsTo(GroupPosition::class, 'gjabatanId', 'gjabatanId');
    }

    public function groupRole()
    {
        return $this->belongsTo(GroupRole::class, 'GRoleId', 'GRoleId');
    }
}
