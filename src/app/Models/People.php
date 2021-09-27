<?php

namespace App\Models;

use App\Enums\PeopleProposedTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class People extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $connection = 'sikdweb';

    public $timestamps = false;

    protected $table = "people";

    protected $primaryKey = 'PeopleId';

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

    public function filter($query, $filter)
    {
        $proposedTo = $filter["proposedTo"] ?? null;

        if ($proposedTo == PeopleProposedTypeEnum::FORWARD()) {
            $query->where('PrimaryRoleId', request()->people->PrimaryRoleId . '.1')     // head of department primary role id
                ->orWhere('PrimaryRoleId', request()->people->PrimaryRoleId . '.1.1');  // sekretary primary role id
        }

        return $query;
    }

    /**
     * OVERRIDE from Laravel\Sanctum\HasApiTokens
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function createToken(string $name, array $abilities = ['*'])
    {
        $token = $this->tokens()->create([
            'id' => Str::orderedUuid(),
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }
}
