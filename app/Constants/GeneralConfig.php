<?php

namespace App\Constants;

class GeneralConfig
{
    public const MESSAGE_DELETION_WINDOW_MINUTES = 15;
    public const MESSAGE_EDIT_WINDOW_MINUTES = 10;
    public const MAX_ATTACHMENT_SIZE_MB = 10;
    public const MAX_ATTACHMENTS_PER_MESSAGE = 5;
    public const ALLOWED_ATTACHMENT_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/gif' => ['gif'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'video/mp4' => ['mp4'],
        'video/quicktime' => ['mov'],
        'video/webm' => ['webm'],
    ];
}
