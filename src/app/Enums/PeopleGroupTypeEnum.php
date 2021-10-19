<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self STRUCTURAL()
 * @method static self SECRETARY()
 * @method static self STAFF()
 * @method static self TU()
 */

class PeopleGroupTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'STRUCTURAL' => 3,
            'SECRETARY' => 4,
            'STAFF' => 7,
            'TU' => 8,
        ];
    }
}
