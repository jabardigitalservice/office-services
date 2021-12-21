<?php

namespace App\Http\Traits;

use Illuminate\Support\Arr;

trait InboxFilterTrait
{
    /**
     * Query that handle three sub querys
     *
     * @return array
     */
    public function threeLvlQuery($query, $requestFilter, $keyColumn, $tables)
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
