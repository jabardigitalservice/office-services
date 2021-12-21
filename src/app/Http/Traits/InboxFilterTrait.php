<?php

namespace App\Http\Traits;

use App\Enums\InboxReceiverScopeType;
use App\Enums\ListTypeEnum;
use Illuminate\Support\Arr;

trait InboxFilterTrait
{
    /**
     * Filtering list by status
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    private function filterByStatus($query, $filter)
    {
        $statuses = $filter["statuses"] ?? null;
        if ($statuses) {
            $arrayStatuses = explode(", ", $statuses);
            $query->whereIn('StatusReceive', $arrayStatuses);
        }
    }

    /**
     * Filtering list by sources types
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    private function filterByResource($query, $filter)
    {
        $sources = $filter["sources"] ?? null;
        if ($sources) {
            $arraySources = explode(", ", $sources);
            $query->whereIn('NId', function ($inboxQuery) use ($arraySources) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('Pengirim', $arraySources);
            });
        }
    }

    /**
     * Filtering list by folder types
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    private function filterByFolder($query, $filter)
    {
        $folder = $filter["forwarded"] ?? null;
        if ($folder) {
            $arrayFolders = explode(", ", $folder);
            $query->whereIn('NId', function ($inboxQuery) use ($arrayFolders) {
                $inboxQuery->select('NId')
                ->from('inbox')
                ->whereIn('NTipe', $arrayFolders);
            });
            $query->where('ReceiverAs', 'to');
        }
    }

    /**
     * Filtering list by forward status types
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    private function filterByForwardStatus($query, $filter)
    {
        $forwarded = $filter["forwarded"] ?? null;
        if ($forwarded) {
            $arrayForwarded = explode(", ", $forwarded);
            $query->whereIn('Status', $arrayForwarded);
        }
    }

    /**
     * Filtering list by receiver types
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    private function filterByReceiverTypes($query, $filter)
    {
        $receiverTypes = $filter["receiverTypes"] ?? null;
        if ($receiverTypes) {
            $arrayReceiverTypes = explode(", ", $receiverTypes);
            $query->whereIn('ReceiverAs', $arrayReceiverTypes);
        }
    }

    /**
     * Filtering list by scope types
     *
     * @param Object $query
     * @param Array $filter
     *
     * @return Void
     */
    private function filterByScope($query, $filter)
    {
        $scope = $filter["scope"] ?? null;
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
    }

    /**
     * Generate department id from user roleId
     *
     * @param String $roleId
     *
     * @return String
     */
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

    /**
     * Filtering list by types
     *
     * @param Object $query
     * @param Array  $filter
     * @param ListTypeEnum $listType
     *
     * @return Void
     */
    private function filterByType($query, $filter, $listType)
    {
        $types = $filter["types"] ?? null;
        if ($types) {
            $firstTable = $this->defineFirstTable($listType);
            $tables = array(
                0 => array('name'  => $firstTable['name'], 'column' => 'JenisId'),
                1 => array('name'  => 'master_jnaskah', 'column' => 'JenisId')
            );
            $this->threeLvlQuery($query, $types, $firstTable['keyColumn'], $tables);
        }
    }

    /**
     * Filtering by inbox urgencies
     *
     * @param Object $query
     * @param Array  $filter
     * @param ListTypeEnum $listType
     *
     * @return array
     */
    private function filterByUrgency($query, $filter, $listType)
    {
        $urgencies = $filter["urgencies"] ?? null;
        if ($urgencies) {
            $firstTable = $this->defineFirstTable($listType);
            $tables = array(
                0 => array('name'  => $firstTable['name'], 'column' => 'UrgensiId'),
                1 => array('name'  => 'master_urgensi', 'column' => 'UrgensiName')
            );
            $this->threeLvlQuery($query, $urgencies, $firstTable['keyColumn'], $tables);
        }
    }

    /**
     * Define the first table to be queried
     *
     * @param ListTypeEnum $listType
     *
     * @return array
     */
    private function defineFirstTable($listType)
    {
        if ($listType === ListTypeEnum::DRAFT_LIST()) {
            return ['name' => 'konsep_naskah', 'keyColumn' => 'NId_Temp'];
        }
        return ['name' => 'inbox', 'keyColumn' => 'NId'];
    }

    /**
     * Query that handle three sub queries
     *
     * @param Object $query
     * @param String $requestFilter
     * @param String $keyColumn
     * @param Array  $tables
     *
     * @return array
     */
    private function threeLvlQuery($query, $requestFilter, $keyColumn, $tables)
    {
        $arrayTypes = explode(", ", $requestFilter);
        $query->whereIn('NId', function ($draftQuery) use ($arrayTypes, $keyColumn, $tables) {
            $draftQuery->select($keyColumn)
            ->from(Arr::get($tables, '0.name'))
            ->whereIn(Arr::get($tables, '0.column'), function ($docQuery) use ($arrayTypes, $tables) {
                $docQuery->select(Arr::get($tables, '0.column'))
                    ->from(Arr::get($tables, '1.name'))
                    ->whereIn(Arr::get($tables, '1.column'), $arrayTypes);
            });
        });
    }
}
