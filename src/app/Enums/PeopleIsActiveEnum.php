<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self ACTIVE()
 * @method static self NOT_ACTIVE()
 */

class PeopleIsActiveEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'ACTIVE' => 1,
            'NOT_ACTIVE' => 0,
        ];
    }
}
