<?php

namespace RDN\Error\Notification;

use Bitrix\Main\Mail;
use Bitrix\Main\Config;
use RDN\Error\Option;

class Email
{
    public static function send(?string $message): void
    {
        $moduleId = 'rdn.error';

        if (
            ! empty($message)
            && ! empty($email = Config\Option::get($moduleId, Option::RECIPIENT_EMAIL))
        ) {
            \CEvent::Send(
                'RDN_ERROR_NOTICE',
                SITE_ID,
                [
                    'EMAIL' => $email,
                    'MESSAGE' => $message
                ]
            );
        }
    }
}
