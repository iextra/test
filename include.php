<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

try {
    Loader::registerAutoLoadClasses('rdn.error', [
        'RDN\Error\Log' => 'lib/Log.php',
        'RDN\Error\Option' => 'lib/Option.php',
        'RDN\Error\HashStorage' => 'lib/HashStorage.php',
        'RDN\Error\Entities\Log' => 'lib/entities/Log.php',
        'RDN\Error\Notification\AdminSection' => 'lib/notification/AdminSection.php',
        'RDN\Error\Notification\Email' => 'lib/notification/Email.php',
        'RDN\Error\Notification\Telegram' => 'lib/notification/Telegram.php',
        'RDN\Error\Entities\Internals\LogTable' => 'lib/entities/internals/LogTable.php',
        'Site\ErrorHandler\Entities\LongtextField' => 'lib/entities/LongtextField.php',
        'Bitrix\Main\Entity\LongtextField' => 'lib/Entities/LongtextField.php',
    ]);

} catch (LoaderException $e) {

}
