<?php

namespace RDN\Error\Notification;

class AdminSection
{
    public static function send(): void
    {
        global $DB;
        global $CACHE_MANAGER;

        $table = 'b_admin_notify';
        $moduleId = 'rdn.error';
        $cacheId = 'admin_notify_list_' . LANGUAGE_ID;
        $type = 'E';

        $arFields = array(
            'MODULE_ID'	=> $moduleId,
            'TAG' => 'SITE_ERROR',
            'MESSAGE' => 'Обнаружены ошибки в работе сайта. <a href="/bitrix/admin/rdn_error_log.php">Посмотреть</a>',
            'ENABLE_CLOSE' => 'Y',
            'PUBLIC_SECTION' => 'N',
            'NOTIFY_TYPE' => $type
        );

        $sql = "SELECT `ID` FROM `{$table}` WHERE `MODULE_ID` = '{$moduleId}' AND `NOTIFY_TYPE` = '{$type}' LIMIT 1";
        if (empty($DB->Query($sql)->Fetch())) {
            $DB->Add($table, $arFields, ['MESSAGE']);
        }

        $CACHE_MANAGER->Clean($cacheId);
    }
}
