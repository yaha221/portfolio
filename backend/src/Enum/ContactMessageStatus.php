<?php

declare(strict_types=1);

namespace App\Enum;

enum ContactMessageStatus: string
{
    // Saved, notification email not sent yet
    case New = 'new';
    case Sent = 'sent';
    // Email failed after all Messenger retries
    case Failed = 'failed';
}
