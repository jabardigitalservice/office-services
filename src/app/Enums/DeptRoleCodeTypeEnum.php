<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self SETDA()
 */

class DeptRoleCodeTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'SETDA' => 3,
        ];
    }
}
