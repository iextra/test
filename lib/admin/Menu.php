<?php

namespace RDN\Error\Admin;

class Menu
{
    public static function buildMenu(&$aGlobalMenu, &$aModuleMenu): void
    {
        $aModuleMenu[] = array(
            'parent_menu' => 'global_menu_services',
            'items_id' => 'rdn_error_log',
            'icon' => 'sender_ads_menu_icon', // 'advertising_menu_icon'
            'page_icon' => 'advertising_menu_icon',
            'sort' => 50,
            'text' => 'Уведомления об ошибках',
            'title' => 'Уведомления об ошибках',
            'items' => [
                [
                    'parent_menu' => 'rdn_error.log',
                    'sort' => 100,
                    'icon' => 'iblock_menu_icon_types',
                    'text' => 'Лог',
                    'title' => 'Лог',
                    'url' => 'rdn_error_log.php',
                ],
                [
                    'parent_menu' => 'rdn_error.settings',
                    'sort' => 200,
                    'icon' => 'sys_menu_icon',
                    'text' => 'Настройки',
                    'title' => 'Настройки',
                    'url' => 'rdn_error_settings.php',
                ],
                [
                    'parent_menu' => 'rdn_error.doc',
                    'sort' => 300,
                    'icon' => 'b24connector_menu_icon',
                    'text' => 'Использование',
                    'title' => 'Использование',
                    'url' => 'rdn_error_doc.php',
                ],
            ],
        );
    }
}
