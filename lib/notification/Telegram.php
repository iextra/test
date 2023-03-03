<?php

namespace RDN\Error\Notification;

use Bitrix\Main\Config;
use RDN\Error\Option;

class Telegram
{
    public static function send(?string $message): void
    {
        $moduleId = 'rdn.error';

        $url = null;
        if (! empty($serverName = Config\Option::get('main', 'server_name'))) {
            $url = 'https://' . $serverName . '/bitrix/admin/rdn_error_log.php';
        }

        if (
            ! empty($message)
            && ! empty($token = Config\Option::get($moduleId, Option::TG_TOKEN))
            && ! empty($chatId = Config\Option::get($moduleId, Option::TG_CHAT_ID))
        ) {

            $getQuery = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'html'
            ];

            if (! empty($url)) {
                $getQuery['reply_markup'] = json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => 'Посмотреть',
                                'url' => $url
                            ],
                        ]
                    ],
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true,
                ]);
            }

            $curl = curl_init("https://api.telegram.org/bot{$token}/sendMessage?" . http_build_query($getQuery));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);

            curl_exec($curl);
            curl_close($curl);
        }
    }
}
