<?php

namespace App\Models;

use App\Enums\ArchiverIdUnitTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Enums\PeopleProposedTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class People extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;

    protected $connection = 'sikdweb';

    public $timestamps = false;

    protected $table = 'people';

    protected $primaryKey = 'PeopleId';

    public function role()
    {
        return $this->belongsTo(Role::class, 'PrimaryRoleId', 'RoleId');
    }

    public function parentRole()
    {
        return $this->belongsTo(Role::class, 'RoleAtasan', 'RoleId');
    }

    public function siapPeople()
    {
        return $this->belongsTo(SiapPeople::class, 'PeopleUsername', 'peg_nip');
    }

    public function filter($query, $filter)
    {
        $this->filterByProposedType($query, $filter);
        return $query;
    }

    /**
     * filter people list based on roleId.
     *
     * @param  Object  $query
     * @param  String  $roleId
     *
     * @return Void
     */
    private function filterByProposedType($query, $filter)
    {
        $user = auth()->user();
        $query->where('PeopleId', '<>', $user->PeopleId);
        $proposedTo = $filter["proposedTo"] ?? null;
        match ($proposedTo) {
            PeopleProposedTypeEnum::FORWARD()->value,
            PeopleProposedTypeEnum::FORWARD_DRAFT()->value => $this->filterForward($query),
            PeopleProposedTypeEnum::DISPOSITION()->value => $this->filterDisposition($query),
            PeopleProposedTypeEnum::FORWARD_DOC_SIGNATURE()->value => $this->filterForwardSignature($query),
            PeopleProposedTypeEnum::NUMBERING_UK()->value => $this->filterNumberingByUK($query),
            PeopleProposedTypeEnum::NUMBERING_TU()->value => $this->filterNumberingByTU($query)
        };
    }

    /**
     * Filter people for forwarding proposed.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function filterForward($query)
    {
        $this->groupExceptionQuery($query);
        $roleId = auth()->user()->PrimaryRoleId;
        $roleIdUnit = count(explode(".", $roleId));
        switch ($roleIdUnit) {
            case ArchiverIdUnitTypeEnum::SETDA()->value:
                // A special condition when the archiver (unit kearsipan) is 'unit kearsipan setda (uk.setda)'
                // uk.setda role id is uk.1.1.1.1.1 (roleIdUnit=6)
                $query->whereIn('PrimaryRoleId', function ($roleQuery) {
                    $roleQuery->select('RoleId')
                        ->from('role')
                        // The forward targets have various role ids
                        // with min. length id is 4, for example uk.1 as the government
                        // and max. length id is 18, for instance uk.1.1.1.1.1.1.1.2 as the bureau chief
                        ->whereRaw('LENGTH(PrimaryRoleId) >= 4 AND LENGTH(PrimaryRoleId) <= 18')
                        ->where('RoleCode', 3);
                });
                break;

            case ArchiverIdUnitTypeEnum::DEPT()->value:
                // Department archivers (unit kearsipan dinas) always have the roleIdUnit=3
                // These are the role id patterns for 'kadis' and 'sekdis' of a department (dinas)
                $query->whereIn('PrimaryRoleId', [$roleId . '.1', $roleId . '.1.1']);
                break;

            default:
                // For another archivers, the people targets are their direct superior roles
                $query->where('PrimaryRoleId', auth()->user()->RoleAtasan);
                break;
        }
    }

    /**
     * Filter people for disposition proposed.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function filterDisposition($query)
    {
        // The disposition targets are the people who has the 'RoleAtasan' as the user's roleId.
        $query->where('RoleAtasan', auth()->user()->PrimaryRoleId)
        // Data from group table: 3=Pejabat Struktural 4=Sekdis 7=Staf
        ->whereIn('GroupId', [
            PeopleGroupTypeEnum::STRUCTURAL()->value,
            PeopleGroupTypeEnum::SECRETARY()->value,
            PeopleGroupTypeEnum::STAFF()->value
        ]);
    }

    /**
     * Filter people for forwarding doc signature proposed.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function filterForwardSignature($query)
    {
        // Data from group table: 6=Unit Kearsipan 8=Tata Usaha
        $peopleTu = People::whereHas('role', function ($role) {
            $role->where('RoleCode', auth()->user()->role->RoleCode);
            $role->where('Code_Tu', auth()->user()->role->Code_Tu);
        })->where('GroupId', PeopleGroupTypeEnum::TU()->value)->pluck('PeopleId');

        $peopleUk = People::whereHas('role', function ($role) {
            $role->where('RoleCode', auth()->user()->role->RoleCode);
            $role->where('GRoleId', auth()->user()->role->GRoleId);
        })->where('GroupId', PeopleGroupTypeEnum::UK()->value)->pluck('PeopleId');

        $peopleIds = Arr::collapse([$peopleTu, $peopleUk]);
        $query->whereIn('PeopleId', $peopleIds);
    }


    /**
     * Filter people for numbering by archiver (UK).
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function filterNumberingByUK($query)
    {
        $query->where('GroupId', PeopleGroupTypeEnum::UK());
        $this->filterNumbering($query);
    }

    /**
     * Filter people for numbering by administration (TU).
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function filterNumberingByTU($query)
    {
        $query->where('GroupId', PeopleGroupTypeEnum::TU())
            ->where('RoleAtasan', auth()->user()->PrimaryRoleId);
        $this->filterNumbering($query);
    }

    /**
     * Filter people for numbering proposed.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function filterNumbering($query)
    {
        $query->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
            ->from('role')
            ->where('GRoleId', auth()->user()->role->GRoleId));
    }

    /**
     * People group exception.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function groupExceptionQuery($query)
    {
        $query->whereNotIn('GroupId', [
            PeopleGroupTypeEnum::TU(),
            PeopleGroupTypeEnum::SETDA_RECIPIENT(),
            PeopleGroupTypeEnum::SETDA_CONTROLLER(),
            PeopleGroupTypeEnum::SETDA_DIRECTOR(),
        ]);
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

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }
}
