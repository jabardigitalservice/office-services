<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = "people";

    protected $keyType = 'string';

    protected $primaryKey = 'PrimaryRoleId';

    public function role()
    {
        return $this->belongsTo(Role::class, 'PrimaryRoleId', 'RoleId');
    }

    public function siapPeople()
    {
        return $this->belongsTo(SiapPeople::class, 'PeopleUsername', 'peg_nip');
    }

    public function getAvatarAttribute()
    {
        return optional($this->siapPeople)->peg_foto_url;
    }
}
