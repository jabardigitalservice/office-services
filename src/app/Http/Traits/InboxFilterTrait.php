<?php

namespace App\Http\Traits;

use App\Enums\InboxFilterTypeEnum;
use App\Enums\InboxReceiverScopeType;
use App\Enums\ListTypeEnum;
use Illuminate\Support\Arr;

/**
 * Filter inbox data with parameters
 */
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
        $folder = $filter["folder"] ?? null;
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
        if ($forwarded || $forwarded != null) {
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
            $arrayTypes = explode(", ", $types);
            $this->inboxCategoryFilterQuery(
                $query,
                $arrayTypes,
                $listType,
                InboxFilterTypeEnum::TYPES()
            );
        }
    }

    /**
     * Filtering by inbox urgencies
     *
     * @param Object $query
     * @param Array  $filter
     * @param ListTypeEnum $listType
     *
     * @return Void
     */
    private function filterByUrgency($query, $filter, $listType)
    {
        $urgencies = $filter["urgencies"] ?? null;
        if ($urgencies) {
            $arrayUrgencies = explode(", ", $urgencies);
            $this->inboxCategoryFilterQuery(
                $query,
                $arrayUrgencies,
                $listType,
                InboxFilterTypeEnum::URGENCIES()
            );
        }
    }

    /**
     * Filtering by inbox followed up status
     *
     * @param Object $query
     * @param Array  $filter
     *
     * @return Void
     */
    private function filterByFollowedUpStatus($query, $filter)
    {
        $followedUp = $filter["followedUp"] ?? null;
        if ($followedUp || $followedUp != null) {
            $arrayFollowedUp = explode(", ", $followedUp);
            $query->whereIn('TindakLanjut', $arrayFollowedUp);
        }
    }

    /**
     * Query for filtering based on filter category.
     *
     * @param Object                $query
     * @param Array                 $keysFilter
     * @param ListTypeEnum          $listType
     * @param InboxFilterTypeEnum   $categoryType
     *
     * @return Void
     */
    private function inboxCategoryFilterQuery($query, $keysFilter, $listType, $categoryType)
    {
        if ($categoryType === InboxFilterTypeEnum::URGENCIES()) {
            $categoryColumn = 'UrgensiId';
            $filterQuery = fn($query) => $this->urgencyQuery($query, $keysFilter);
        } else {
            $categoryColumn = 'JenisId';
            $filterQuery = fn($query) => $this->typeQuery($query, $keysFilter);
        }

        $table = $this->defineListTable($listType);
        $query->whereIn('NId', fn($query) => $query->select(Arr::get($table, 'key'))
            ->from(Arr::get($table, 'name'))
            ->whereIn($categoryColumn, $filterQuery));
    }

    /**
     * Define the chosen table.
     *
     * @param ListTypeEnum $listType
     *
     * @return Array
     */
    private function defineListTable($listType)
    {
        if ($listType === ListTypeEnum::DRAFT_LIST()) {
            return array('name' => 'konsep_naskah', 'key' => 'NId_Temp');
        }
        return array('name' => 'inbox', 'key' => 'NId');
    }

    /**
     * Query for filtering based on inbox type table.
     *
     * @param Object $query
     * @param Array $keysFilter
     *
     * @return Void
     */
    private function typeQuery($query, $keysFilter)
    {
        $query->select('JenisId')->from('master_jnaskah')->whereIn('JenisId', $keysFilter);
    }

    /**
     * Query for filtering based on urgency table.
     *
     * @param Object $query
     * @param Array $keysFilter
     *
     * @return Void
     */
    private function urgencyQuery($query, $keysFilter)
    {
        $query->select('UrgensiId')->from('master_urgensi')->whereIn('UrgensiName', $keysFilter);
    }
}
