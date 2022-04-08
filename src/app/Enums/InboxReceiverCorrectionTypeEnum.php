<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self NUMBERING()
 * @method static self SIGNED()
 */

final class InboxReceiverCorrectionTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'NUMBERING' => 'Meminta Nomber Surat',
            'SIGNED' => 'approvenaskah',
        ];
    }
}
