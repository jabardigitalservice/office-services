<?php

namespace App\Models;

use App\Enums\DeptRoleCodeTypeEnum;
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
            PeopleProposedTypeEnum::NUMBERING_TU()->value => $this->filterNumberingByTU($query),
            PeopleProposedTypeEnum::DISTRIBUTE()->value => $this->filterDistribute($query),
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
        $roleId = auth()->user()->PrimaryRoleId;
        if ($roleId != PeopleRoleIdTypeEnum::UKSETDA()->value) {
            $superiorId = auth()->user()->RoleAtasan;
            $superiorPosition = People::where('PrimaryRoleId', $superiorId)->first()->PeoplePosition;
            if ($this->isALeader($superiorPosition)) {
                // will return the user seperior (atasan) and the secretary
                $query->whereIn('PrimaryRoleId', [$superiorId, $superiorId . '.1']);
            } else {
                $query->where('PrimaryRoleId', $superiorId);
            }
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
        $userPosition = auth()->user()->PeoplePosition;
        $positions = config('constants.peoplePositionGroups');
        $positionGroup = '';
        $positionGroup = $this->dispositionGroup1Query($query, $userPosition, $positions) ?? $positionGroup;
        $positionGroup = $this->dispositionGroup2Query($query, $userPosition, $positions) ?? $positionGroup;
        $positionGroup = $this->dispositionGroup3Query($query, $userPosition, $positions) ?? $positionGroup;
        $positionGroup = $this->dispositionGroup4Query($query, $userPosition, $positions) ?? $positionGroup;
        $this->dispositionGroupDefaultQuery($query, $positionGroup);
    }

    /**
     * Filter people for Positions Group default disposition proposed
     *
     * @param  Object  $query
     * @param  String  $hasGroup
     *
     * @return Void
     */
    private function dispositionGroupDefaultQuery($query, $hasGroup)
    {
        if (!$hasGroup) {
            $query->where('RoleAtasan', auth()->user()->PrimaryRoleId)
                ->whereIn('GroupId', [
                    PeopleGroupTypeEnum::STRUCTURAL()->value,
                    PeopleGroupTypeEnum::SECRETARY()->value,
                    PeopleGroupTypeEnum::STAFF()->value
                ]);
        }
    }

     /**
     * Filter people for Positions Group 1 disposition proposed
     *
     * @param  Object  $query
     * @param  String  $userPosition
     * @param  Array   $positionsGroup
     *
     * @return String
     */
    private function dispositionGroup1Query($query, $userPosition, $positionsGroup)
    {
        if ($userPosition == $positionsGroup[1][0]) {
            $query->where(
                fn($query) => $query
                    ->where('PeoplePosition', 'LIKE', $positionsGroup[3][0] . '%')
                    ->orWhere('PeoplePosition', 'LIKE', $positionsGroup[3][1] . '%')
                    ->orWhere('PeoplePosition', 'LIKE', $positionsGroup[3][2] . '%')
                    ->orWhere('PeoplePosition', 'LIKE', $positionsGroup[3][3] . '%')
                    ->orWhere('PeoplePosition', 'LIKE', $positionsGroup[3][4] . '%')
                    ->orWhere('PeoplePosition', 'LIKE', $positionsGroup[3][5] . '%')
                    ->orWhere('PeoplePosition', 'LIKE', $positionsGroup[3][6] . '%')
                    ->orWhereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
                        ->from('role')
                        ->where('RoleCode', DeptRoleCodeTypeEnum::SETDA()->value))
                    ->where('GroupId', PeopleGroupTypeEnum::STRUCTURAL()->value)
            );

            return 'GROUP_1';
        }
    }

    /**
     * Filter people for Positions Group 2 disposition proposed
     *
     * @param  Object  $query
     * @param  String  $userPosition
     * @param  Array   $positionsGroup
     *
     * @return String
     */
    private function dispositionGroup2Query($query, $userPosition, $positionsGroup)
    {
        if ($userPosition == $positionsGroup[2][0] || $userPosition == $positionsGroup[2][1]) {
            $this->dispositionViceGovernorQuery($query, $userPosition, $positionsGroup);
            $this->dispositionSEKDAQuery($query, $userPosition, $positionsGroup);
            return 'GROUP_2';
        }
    }

     /**
     * Filter people for Positions Group 3 disposition proposed
     *
     * @param  Object  $query
     * @param  String  $userPosition
     * @param  Array   $positionsGroup
     *
     * @return String
     */
    private function dispositionGroup3Query($query, $userPosition, $positionsGroup)
    {
        // Check if the user is belong to group 3
        $isPosition = $this->isBelongToGroup($userPosition, $positionsGroup[3]);
        if ($isPosition) {
            $this->dispositionLeaderQuery($query);
            return 'GROUP_3';
        }
    }

    /**
     * Filter people for Positions Group 4 disposition proposed
     *
     * @param  Object  $query
     * @param  String  $userPosition
     * @param  Array   $positionsGroup
     *
     * @return String
     */
    private function dispositionGroup4Query($query, $userPosition, $positionsGroup)
    {
        // Check if the user is belong to group 4
        $isPosition = $this->isBelongToGroup($userPosition, $positionsGroup[4]);
        if ($isPosition) {
            $this->dispositionLeaderQuery($query);
            $query->where('PeoplePosition', 'NOT LIKE', $positionsGroup[3][5] . '%');
            $query->where('PrimaryRoleId', 'NOT LIKE', auth()->user()->RoleAtasan);
            return 'GROUP_4';
        }
    }

    /**
     * Filter people for Positions Group 2 - Vice Governor disposition proposed
     *
     * @param  Object  $query
     * @param  String  $userPosition
     * @param  Array   $positionsGroup
     *
     * @return Void
     */
    private function dispositionViceGovernorQuery($query, $userPosition, $positionsGroup)
    {
        if ($userPosition == $positionsGroup[2][0]) {
            $query->where(
                fn($query) => $query
                    ->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
                        ->from('role')
                        ->where('RoleCode', DeptRoleCodeTypeEnum::SETDA()->value))
                    ->where('PrimaryRoleId', '!=', PeopleRoleIdTypeEnum::GOVERNOR())
                    ->where('GroupId', PeopleGroupTypeEnum::STRUCTURAL()->value)
            );
        }
    }

    /**
     * Filter people for Positions Group 2 - SEKDA disposition proposed
     *
     * @param  Object  $query
     * @param  String  $userPosition
     * @param  Array   $positionsGroup
     *
     * @return Void
     */
    private function dispositionSEKDAQuery($query, $userPosition, $positionsGroup)
    {
        if ($userPosition == $positionsGroup[2][1]) {
            $query->where(
                fn($query) => $query
                    ->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
                        ->from('role')
                        ->where('RoleCode', DeptRoleCodeTypeEnum::SETDA()->value))
                    ->where('PrimaryRoleId', '!=', PeopleRoleIdTypeEnum::GOVERNOR())
                    ->where('PrimaryRoleId', '!=', PeopleRoleIdTypeEnum::VICE_GOVERNOR())
                    ->where('GroupId', PeopleGroupTypeEnum::STRUCTURAL()->value)
            );
        }
    }

    /**
     * Filter people for Leader position disposition proposed
     *
     * @param  Object  $query
     *
     * @return Void
     */
    private function dispositionLeaderQuery($query)
    {
        $query->where('PrimaryRoleId', '!=', 'root')
            ->whereNotIn('GroupId', [
                PeopleGroupTypeEnum::ADMIN()->value,
                PeopleGroupTypeEnum::UK()->value,
                PeopleGroupTypeEnum::TU()->value
            ])->whereIn('PrimaryRoleId', fn($query) => $query->select('RoleId')
                ->from('role')
                ->where('RoleCode', auth()->user()->role->RoleCode));
    }

    /**
     * Check people position group
     *
     * @param  Array $positionsGroup
     *
     * @return Boolean
     */
    private function isBelongToGroup($userPosition, $positionsGroup)
    {
        foreach ($positionsGroup as $position) {
            if (strpos($userPosition, $position) !== false) {
                return true;
            }
        }
        return false;
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
     * Filter people for distribute document (UK).
     *
     * @param  Object  $query
     */
    private function filterDistribute($query)
    {
        $query->where('GroupId', PeopleGroupTypeEnum::UK())
            ->whereNotIn('RoleAtasan', ['', '-']);
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
