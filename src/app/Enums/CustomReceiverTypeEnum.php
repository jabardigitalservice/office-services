<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self CORRECTION()
 * @method static self NUMBERING()
 * @method static self SIGN_REQUEST()
 * @method static self SIGNED()
 * @method static self REVIEW()
 * @method static self DISTRIBUTION()
 */

final class CustomReceiverTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'CORRECTION' => 'CORRECTION',
            'NUMBERING' => 'NUMBERING',
            'SIGN_REQUEST' => 'SIGN_REQUEST',
            'SIGNED' => 'SIGNED',
            'REVIEW' => 'REVIEW',
        ];
    }
}
