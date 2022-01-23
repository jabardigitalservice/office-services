<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self WAITING()
 * @method static self SUCCESS()
 * @method static self REJECT()
 * @method static self SIGNED()
 * @method static self UNSIGNED()
 */

class SignatureStatusTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'WAITING' => 0,
            'SUCCESS' => 1,
            'REJECT' => 4,
        ];
    }
}
