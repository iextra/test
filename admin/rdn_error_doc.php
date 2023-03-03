<?php

global $USER;
global $APPLICATION;
global $DB;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use RDN\Error\Entities\Internals\LogTable;
use RDN\Error\Log;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/prolog.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/classes/general/csv_user_import.php';

if (! $USER->CanDoOperation('edit_php')) {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

$APPLICATION->SetTitle('Уведомления об ошибках - использование');

require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';
?>

<div class="adm-detail-content-wrap" style="padding: 10px">
    Для лоргирования ошибок используется метод:
    <br>
    <br>
    <?= highlight_string('<?php
use RDN\Error\Log;
            
Log::add(
    "Текст ошибки",                       // [Обязательный] - Сообщение
    $data,                              // [Не обязательный] - Любые данные для отладки
    Log::SECTION__PRICE_ANALYTICS,      // [Не обязательный] - Раздел
    Log::ERROR_LEVEL__CRITICAL          // [Не обязательный] - Уровень ошибки (CRITICAL, ERROR, WARNING)
);', true); ?>
    <br>
    <br>
    Для откладки в скрипте можно объявить константу <b>'STOP_RDN_ERROR'</b><br>
    тогда сообщения об ошибках не будут попадать в лог и отправляться в уведомлениях
</div>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';