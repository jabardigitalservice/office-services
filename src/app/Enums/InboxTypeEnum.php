<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self INBOX()
 * @method static self OUTBOX()
 * @method static self OUTBOXNOTADINAS()
 */

final class InboxTypeEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'INBOX' => 'inbox',
            'OUTBOX' => 'outbox',
            'OUTBOXNOTADINAS' => 'outboxnotadinas',
        ];
    }
}
