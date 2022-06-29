<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self INTERNAL()
 * @method static self EXTERNAL()
 */

final class InboxOriginTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'INTERNAL' => 'internal',
            'EXTERNAL' => 'eksternal',
        ];
    }
}
