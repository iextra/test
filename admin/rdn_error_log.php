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

if(! $USER->CanDoOperation('edit_php')){
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

Loader::includeModule('rdn.error');

$table = LogTable::getTableName();
$sort = new CAdminSorting($table, 'ID', 'DESC');
$lAdmin = new CAdminList($table, $sort);
$request = Context::getCurrent()->getRequest();

// ******************************************************************** //
//                              ФИЛЬТР                                  //
// ******************************************************************** //

$lAdmin->InitFilter([
    'FIND_MESSAGE',
    'FIND_SECTION',
    'FIND_ERROR_LEVEL',
    'FIND_DATE_CREATE'
]);

$arFilter = [];

if (isset($FIND_MESSAGE) && $FIND_MESSAGE != '') {
    $arFilter['MESSAGE'] = '%' . $FIND_MESSAGE . '%';
}

if (isset($FIND_SECTION) && $FIND_SECTION != '') {
    $arFilter['SECTION'] = $FIND_SECTION;
}

if (isset($FIND_ERROR_LEVEL) && $FIND_ERROR_LEVEL != '') {
    $arFilter['ERROR_LEVEL'] = $FIND_ERROR_LEVEL;
}

if (isset($FIND_DATE_CREATE) && $FIND_DATE_CREATE != '') {
    try {
        $dateTime = new DateTime($FIND_DATE_CREATE);
        $arFilter['>DATE_CREATE'] = $dateTime->format('d.m.Y') . ' 00:00:01';
        $arFilter['<DATE_CREATE'] = $dateTime->format('d.m.Y') . ' 23:59:59';

        $FIND_DATE_CREATE = $dateTime->format('d.m.Y');
    } catch (Exception $e) {}

}

if ($request->get('set_filter') !== 'Y') {
    $arFilter = [];
}

$oFilter = new CAdminFilter(
    $table . '_filter',
    ['Раздел', 'Дата', 'Сообщение', 'Уровень ошибки']
);

// ******************************************************************** //
//                ДЕТАЛЬНОЕ ОТОБРАЖЕНИЕ ОШИБКИ                          //
// ******************************************************************** //

if (
    $request->isAjaxRequest()
    && $request->get('action') === 'showErrorDetail'
    && !empty($errorId = $request->get('id'))
) {
    try {
        $item = LogTable::query()
            ->setSelect(['*'])
            ->where('ID', $errorId)
            ->setLimit(1)
            ->exec()
            ->fetch();

        if (! $item) {
            throw new \Exception("Запись с ID {$errorId} не найдена");
        }
    }
    catch (\Exception $e) {
        $item = [
            'INTERNAL_ERROR' => $e->getMessage()
        ];
    }

    if (! empty($item['DATE_CREATE'])) {
        $item['DATE_CREATE'] = FormatDateFromDB($item['DATE_CREATE'], 'DD.MM.YYYY HH:MI:SS');
    }

    if (! empty($errorLevel = $item['ERROR_LEVEL'])) {
        $item['ERROR_LEVEL'] = match ($errorLevel) {
            Log::ERROR_LEVEL__CRITICAL => '<span class="error-critical">Критическая</>',
            Log::ERROR_LEVEL__ERROR => '<span class="error-error">Ошибка</>',
            Log::ERROR_LEVEL__WARNING => '<span class="error-warning">Предупреждение</>',
            default => $errorLevel
        };
    }

    echo json_encode($item);
    die();
}

// ******************************************************************** //
//                ОБРАБОТКА ДЕЙСТВИЙ НАД ЭЛЕМЕНТАМИ СПИСКА              //
// ******************************************************************** //

// Обработка одиночных и групповых действий
if (! empty($arIds = $lAdmin->GroupAction())) {

    try {
        @set_time_limit(0);

        $query = LogTable::query()
            ->setSelect(['ID']);

        if (! empty($arIds[0])){
            $query->whereIn('ID', $arIds);
        }

        $itemCollection = $query
            ->exec()
            ->fetchCollection();

        foreach ($itemCollection as $item) {

            // удаление
            if ($_REQUEST['action'] == 'delete') {
                $deleteResult = $item->delete();

                if (!$deleteResult->isSuccess()) {
                    $lAdmin->AddGroupError('Ошибка удаления', $item->getId());
                }
            }
        }
    }
    catch (\Exception $e) {

    }
}

// Преобразуем список в экземпляр класса CAdminResult

try {
    $query = LogTable::query()
        ->setSelect(['ID', 'MESSAGE', 'DATE_CREATE', 'SECTION', 'ERROR_LEVEL'])
        ->setFilter($arFilter)
        ->setOrder([$sort->getField() => $sort->getOrder()])
        ->exec();
}
catch (\Exception $e) {
    ShowError($e->getMessage());
    die;
}

$rsData = new CAdminResult($query, $table);

// Аналогично CDBResult инициализируем постраничную навигацию.
$rsData->NavStart();

// Отправим вывод переключателя страниц в основной объект $lAdmin
$lAdmin->NavText($rsData->GetNavPrint('Показано:'));

$lAdmin->AddHeaders([
    [
        'id' => 'ID',
        'content' => 'ID',
        'sort' => 'ID',
        'align' => '',
        'default' => true,
    ],
    [
        'id' => 'DETAIL',
        'content' => 'Подробнее',
        'sort' => '',
        'align' => '',
        'default' => true,
    ],
    [
        'id' => 'MESSAGE',
        'content' => 'Ошибка',
        'sort' => '',
        'align' => '',
        'default' => true,
    ],
    [
        'id' => 'SECTION',
        'content' => 'Раздел',
        'sort' => '',
        'align' => '',
        'default' => true,
    ],
    [
        'id' => 'ERROR_LEVEL',
        'content' => 'Уровень ошибки',
        'sort' => '',
        'align' => '',
        'default' => true,
    ],
    [
        'id' => "DATE_CREATE",
        'content' => 'Дата создания',
        'sort' => 'DATE_CREATE',
        'align' => '',
        'default' => true,
    ],
]);

while ($arItem = $rsData->Fetch()) {

    if (! empty($errorLevel = $arItem['ERROR_LEVEL'])) {
        $arItem['ERROR_LEVEL'] = match ($errorLevel) {
            Log::ERROR_LEVEL__CRITICAL => '<span class="error-critical">Критическая</>',
            Log::ERROR_LEVEL__ERROR => '<span class="error-error">Ошибка</>',
            Log::ERROR_LEVEL__WARNING => '<span class="error-warning">Предупреждение</>',
            default => $errorLevel
        };
    }

    $row =& $lAdmin->AddRow($arItem['ID'], $arItem);
    $row->AddViewField('DETAIL', '<a href="#" class="js-detail-error" data-id="' . $arItem['ID'] . '">Подробнее</a>');
    $row->AddInputField('MESSAGE',  ['size' => 80]);
    $row->AddInputField('SECTION', ['size' => 10]);
    $row->AddViewField('ERROR_LEVEL', $arItem['ERROR_LEVEL']);
    $row->AddCalendarField('DATE_CREATE', ['size' => 20]);
}

$lAdmin->bCanBeEdited = null;
$lAdmin->AddGroupActionTable(
    ['delete' => GetMessage('MAIN_ADMIN_LIST_DELETE')],
    ['disable_action_target' => false]
);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle('Лог ошибок');

require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';

\CJSCore::Init(['jquery']);
?>

    <form name="find_form" method="get" action="<?= $APPLICATION->GetCurPage();?>">
        <?php $oFilter->Begin(); ?>
        <tr>
            <td>Сообщение:</td>
            <td><input type="text" name="FIND_MESSAGE" size="47" value="<?= htmlspecialchars($FIND_MESSAGE)?>"></td>
        </tr>
        <tr>
            <td>Дата:</td>
            <td><input type="text" name="FIND_DATE_CREATE" size="47" value="<?= htmlspecialchars($FIND_DATE_CREATE)?>"></td>
        </tr>
        <tr>
            <td>Раздел:</td>
            <td>
                <select name="FIND_SECTION">
                    <option value="">Все</option>
                    <option value="<?= Log::SECTION__PRICE_ANALYTICS ?>">Модуль анализа</option>
                    <option value="<?= Log::SECTION__PARSING_SITES ?>">Парсинг сайтов</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Уровень ошибки:</td>
            <td>
                <select name="FIND_ERROR_LEVEL">
                    <option value="">Все</option>
                    <option value="<?= Log::ERROR_LEVEL__CRITICAL ?>">Критическая</option>
                    <option value="<?= Log::ERROR_LEVEL__ERROR ?>">Ошибка</option>
                    <option value="<?= Log::ERROR_LEVEL__WARNING ?>">Предупреждение</option>
                </select>
            </td>
        </tr>
        <?php
        $oFilter->Buttons(['table_id' => $table, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
        $oFilter->End();
        ?>
    </form>

    <?php $lAdmin->DisplayList(); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function (){
            $(document).on('click', '.js-detail-error', function (e){
               e.preventDefault();

               let id = $(this).data('id');
               $.ajax({
                   type: 'post',
                   data: {id: id, action: 'showErrorDetail'},
                   success: function (jsonResponse){
                       let result = JSON.parse(jsonResponse);
                       let border = result.INTERNAL_ERROR ? '0' : '1';

                       let html = '<div><code><table class="error-detail-table border-' + border + '" border="' + border + '">';

                       if (result.INTERNAL_ERROR && result.INTERNAL_ERROR.length > 0) {
                           html += '<tr><td><b class="internal-error">' + result.INTERNAL_ERROR + '</b></td></tr>';
                       }

                       if (result.ID && result.ID > 0) {
                           html += '<tr><td>ID: </td><td><pre>' + result.ID + '</pre></td></tr>';
                       }

                       if(result.MESSAGE && result.MESSAGE.length > 0){
                           html += '<tr><td>MESSAGE: </td><td>' + result.MESSAGE + '</td></tr>';
                       }

                       if(result.ENTITY && result.ENTITY.length > 0){
                           html += '<tr><td>ENTITY: </td><td><pre>' + result.ENTITY + '</pre></td></tr>';
                       }

                       if(result.FUNCTION && result.FUNCTION.length > 0){
                           html += '<tr><td>FUNCTION: </td><td><pre>' + result.FUNCTION + '</pre></td></tr>';
                       }

                       if(result.CALLED_FROM && result.CALLED_FROM.length > 0){
                           html += '<tr><td>CALLED_FROM: </td><td><pre>' + result.CALLED_FROM + '</pre></td></tr>';
                       }

                       if(result.DUMP && result.DUMP.length > 0){
                           html += '<tr><td>DUMP: </td><td><div class="error-dump"><a href="#">Показать</a><br><pre>' + result.DUMP + '</pre></div></td></tr>';
                       }

                       if(result.DATE_CREATE && result.DATE_CREATE.length > 0){
                           html += '<tr><td>DATE_CREATE: </td><td><pre>' + result.DATE_CREATE + '</pre></td></tr>';
                       }

                       if(result.SECTION && result.SECTION.length > 0){
                           html += '<tr><td>SECTION: </td><td><pre>' + result.SECTION + '</pre></td></tr>';
                       }

                       if(result.ERROR_LEVEL && result.ERROR_LEVEL.length > 0){
                           html += '<tr><td>ERROR_LEVEL: </td><td><pre>' + result.ERROR_LEVEL + '</pre></td></tr>';
                       }

                       html += '</table></code><div>';

                       var popup = new BX.CDialog({
                           'title': 'Ошибка',
                           'content': html,
                           'draggable': false,
                           'resizable': false,
                           'width': '900',
                           'height': '600',
                           //'buttons': [BX.CDialog.btnClose]
                       });

                       popup.Show();
                   }
               })
            });

            $(document).on('click', '.error-dump a', function (){
                let parentNode = $(this).parent();
                if(!$(parentNode).hasClass('show')){
                    $(parentNode).addClass('show');
                    $(parentNode).find('a').text('Скрыть');
                }
                else{
                    $(parentNode).removeClass('show');
                    $(parentNode).find('a').text('Показать');
                }
            })
        });
    </script>
    <style>
        .error-detail-table.border-1 {border-collapse: collapse; border: 1px #cecccc solid;}
        .error-detail-table td {padding: 5px; vertical-align: top;}
        .error-detail-table td:first-child {text-align: right; color: #607d8b;}
        .error-detail-table pre {margin: 0;}
        .error-detail-table .error-dump pre {display: none;}
        .error-detail-table .error-dump.show pre {display: block;}
        .error-detail-table .internal-error {color: red;}
        .error-critical {color: #f800e3;}
        .error-error {color: red;}
        .error-warning {color: #f85f00;}
    </style>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
