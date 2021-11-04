<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self SETDA()
 * @method static self DEPT()
 */

class ArchiverIdUnitTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'SETDA' => 6,
            'DEPT' => 3,
        ];
    }
}
