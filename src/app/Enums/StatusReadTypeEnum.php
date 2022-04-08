<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self READ()
 * @method static self UNREAD()
 */

class StatusReadTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'READ' => 1,
            'UNREAD' => 0
        ];
    }
}
