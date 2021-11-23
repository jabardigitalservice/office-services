<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self TO_CORRECTION()
 */

class InboxReceiverCorrectionReceiverType extends Enum
{
    protected static function values(): array
    {
        return [
            'TO_CORRECTION' => 'to_koreksi',
        ];
    }
}
