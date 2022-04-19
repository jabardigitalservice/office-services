<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self GOVERNOR()
 * @method static self VICE_GOVERNOR()
 * @method static self UKSETDA()
 */

class PeopleRoleIdTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'GOVERNOR' => 'uk.1',
            'VICE_GOVERNOR' => 'uk.1.1.1',
            'UKSETDA' => 'uk.1.1.1.1.1',
        ];
    }
}
