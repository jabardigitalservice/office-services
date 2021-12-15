<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self APPROVED()
 * @method static self REGISTERED()
 * @method static self SENT()
 * @method static self CANCELLED()
 */

class DraftConceptStatusTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'APPROVED' => 0,
            'REGISTERED' => 1,
            'SENT' => 2,
            'CANCELLED' => 5,
        ];
    }
}
