<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self STRUCTURAL()
 * @method static self SECRETARY()
 * @method static self STAFF()
 * @method static self TU()
 * @method static self SETDA_RECIPIENT()
 * @method static self SETDA_CONTROLLER()
 * @method static self SETDA_DIRECTOR()
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
            'SETDA_RECIPIENT' => 10,
            'SETDA_CONTROLLER' => 11,
            'SETDA_DIRECTOR' => 12,
        ];
    }
}
