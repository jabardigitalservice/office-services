<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self UKSETDA()
 */

class PeopleRoleIdTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'UKSETDA' => 'uk.1.1.1.1.1',
        ];
    }
}
