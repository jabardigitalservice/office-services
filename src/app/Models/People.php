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
            $roleId = auth()->user()->PrimaryRoleId;
            $query->where('NIP', '<>', null);

            // A special condition when the archiver (unit kearsipan) is 'unit kearsipan setda (uk.setda)'
            // uk.setda role id is uk.1.1.1.1.1
            if ($roleId == 'uk.1.1.1.1.1') {
                $query->whereIn('PrimaryRoleId', function ($roleQuery){
                    $roleQuery->select('RoleId')
                        ->from('role')
                        // The forward targets have various role ids
                        // with min. length id is 4, for example uk.1 as the government
                        // and max. length id is 18, for instance uk.1.1.1.1.1.1.1.2 as the bureau chief
                        ->whereRaw('LENGTH(PrimaryRoleId) >= 4 AND LENGTH(PrimaryRoleId) <= 18')
                        // This fixed role code means the forward targets in the same institution with the uk.setda
                        ->where('RoleCode', 3);
                });
            } else {
                // The role id pattern for 'kadis' and 'sekdis' of a department (dinas)
                $query->whereIn('PrimaryRoleId', [$roleId . '.1', $roleId . '.1.1']);
            }
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
