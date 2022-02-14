<?php

namespace App\Models;

use App\Enums\ArchiverIdUnitTypeEnum;
use App\Enums\PeopleGroupTypeEnum;
use App\Enums\PeopleProposedTypeEnum;
use App\Enums\PeopleRoleIdTypeEnum;
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
        $this->ukFilterForward($query);
        $this->defaultFilterForward($query);
    }

    /**
     * Filter people for forwarding proposed
     * for UK Setda list.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function ukFilterForward($query)
    {
        $this->groupExceptionQuery($query);
        $roleId = auth()->user()->PrimaryRoleId;
        if ($roleId == PeopleRoleIdTypeEnum::UKSETDA()->value) {
            $query->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
                ->from('role')
                ->whereRaw('LENGTH(PrimaryRoleId) >= 4 AND LENGTH(PrimaryRoleId) <= 18')
                ->where('RoleCode', 3));
        }
    }

    /**
     * Filter people for forwarding proposed
     * for default list.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function defaultFilterForward($query)
    {
        $superiorId = auth()->user()->RoleAtasan;
        $superiorPosition = People::where('PrimaryRoleId', $superiorId)->first()->PeoplePosition;
        if ($this->isALeader($superiorPosition)) {
            // will return the user seperior (atasan) and the secretary
            $query->whereIn('PrimaryRoleId', [$superiorId, $superiorId . '.1']);
        } else {
            $query->where('PrimaryRoleId', $superiorId);
        };
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
        $userRole = auth()->user()->role->RoleName;
        if ($userRole == 'GUBERNUR JAWA BARAT' || $userRole == 'WAKIL GUBERNUR JAWA BARAT') {
            $this->governorNumberingByUK($query);
        } else {
            $this->filterNumbering($query);
        }
        $query->where('GroupId', PeopleGroupTypeEnum::UK())
            ->where('PeoplePosition', 'like', "UNIT KEARSIPAN%");
    }

    /**
     * Numbering by archiver (UK) for the governor.
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function governorNumberingByUK($query)
    {
        $query->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
            ->from('role')
            ->where('Code_Tu', 'uk.setda'));
    }

    /**
     * Filter people for numbering by administration (TU).
     *
     * @param  Object  $query
     */
    private function filterNumberingByTU($query)
    {
        $this->filterNumbering($query);
        $query->where('GroupId', PeopleGroupTypeEnum::TU());
        $userPosition = auth()->user()->PeoplePosition;
        if (!$this->isALeader($userPosition) && $this->hasArchiver()) {
            $query->where('RoleAtasan', auth()->user()->PrimaryRoleId);
        } elseif (!$this->isALeader($userPosition) && !$this->hasArchiver()) {
            $query->where('RoleAtasan', auth()->user()->RoleAtasan);
        }
    }

    /**
     * User department archiver (TU) checker
     *
     * @return Mixed
     */
    private function hasArchiver()
    {
        $archiver = People::where('GroupId', PeopleGroupTypeEnum::TU())
            ->where('RoleAtasan', auth()->user()->PrimaryRoleId)->get();

        if (!count($archiver)) {
            return false;
        }
        return true;
    }

    /**
     * Is a leader position checking.
     *
     * @param String $userPosition
     *
     * @return Boolean
     */
    private function isALeader($userPosition)
    {
        $positions = config('constants.peoplePositionGroups');
        $leadersPositions = array_merge($positions[1], $positions[3]);
        foreach ($leadersPositions as $position) {
            if (strpos($userPosition, $position) !== false) {
                return true;
            }
        }
        return false;
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
