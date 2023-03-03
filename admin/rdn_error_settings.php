<?php

global $USER;
global $APPLICATION;
global $DB;

use Bitrix\Main\Loader;
use RDN\Error\Option;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/prolog.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';

if (! $USER->CanDoOperation('edit_php')) {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

$moduleId = 'rdn.error';

if (! Loader::includeModule($moduleId)) {
    $APPLICATION->ThrowException("Module '{$moduleId}' not found.");
}

$arTabs = [
    [
        'DIV' => 'tab_1',
        'TAB' => 'Настройки',
        'FIELDS' => [
            Option::ENABLED_ADMIN_SECTION_NOTICE => [
                'TYPE' => 'checkbox',
                'TITLE' => 'Показывать в админке'
            ],
            Option::ENABLED_ADMIN_SECTION_NOTICE . '_hr' => [
                'TYPE' => 'hr',
            ],

            Option::ENABLED_TG_NOTICE => [
                'TYPE' => 'checkbox',
                'TITLE' => 'Отправлять в телеграм'
            ],
            Option::TG_TOKEN => [
                'TYPE' => 'text',
                'TITLE' => '<b>Токен</b>:'
            ],
            Option::TG_CHAT_ID => [
                'TYPE' => 'text',
                'TITLE' => '<b>ID чата</b>:'
            ],
            Option::ENABLED_TG_NOTICE . '_hr' => [
                'TYPE' => 'hr',
            ],

            Option::ENABLED_EMAIL_NOTICE => [
                'TYPE' => 'checkbox',
                'TITLE' => 'Отправлять на EMAIL'
            ],
            Option::RECIPIENT_EMAIL => [
                'TYPE' => 'text',
                'TITLE' => '<b>EMAIL получателей</b> <span class="gray">[через запятую]</span>:'
            ],
            Option::ENABLED_EMAIL_NOTICE . '_hr' => [
                'TYPE' => 'hr',
            ],

            Option::AVAILABLE_SITES => [
                'TYPE' => 'text',
                'TITLE' => 'Сайты <span class="gray">[через запятую]</span>:'
            ],
            Option::AVAILABLE_SITES . '_info' => [
                'TYPE' => 'info',
                'TEXT' => 'Если указаны сайты, то уведомления будут 
                    <br>отправляться только с перечесленных доменов.
                    <br>
                    <br>Название домена так же долно 
                    <br>быть указано в <a href="/bitrix/admin/settings.php?mid=main" target="_blanc">настройках</a> 
                    главного модуля
                    '
            ],
        ]
    ],
];

$request = Bitrix\Main\Context::getCurrent()->getRequest();
$postData = $request->getPostList();

foreach ($arTabs as $arTab){
    foreach ($arTab['FIELDS'] as $key => $title){
        if (isset($postData[$key])) {
            $value = htmlspecialchars(trim($postData[$key]));
            COption::SetOptionString($moduleId, $key, $value);
        }
    }
}

$tabControl = new CAdminTabControl("tabControl", $arTabs, true, true);
$APPLICATION->SetTitle('Уведомления об ошибках');

\CJSCore::Init(['jquery']);
?>

    <form method="post">
        <?php
        $tabControl->Begin();

        foreach ($arTabs as $arTab){
            $tabControl->BeginNextTab();

            $tabProperties = $arTab['FIELDS'];
            foreach ($tabProperties as $propId => $arProp){
                $value = \COption::GetOptionString($moduleId, $propId);
                ?>
                <tr class="<?= ($arProp['TYPE'] == 'hr') ? 'heading' : '' ?>">

                    <?php if ($arProp['TYPE'] == 'hr') { ?>
                        <td colspan="2">
                            <b><?=$arProp['TEXT']?></b>
                        </td>
                    <?php
                        continue;
                    } else { ?>
                        <td>
                            <?=$arProp['TITLE']?>
                        </td>
                    <?php } ?>

                    <td>
                        <div class="row mb-2">
                            <div class="col ta-c">
                                <?php if ($arProp['TYPE'] === 'text') { ?>

                                    <input type="text" name="<?= $propId ?>" value="<?= $value ?>" size="50">

                                <?php } elseif($arProp['TYPE'] === 'checkbox') {
                                    $switch = ($value === 'Y') ? 'switch-on' : '';
                                    ?>

                                    <input type="hidden" name="<?= $propId ?>" value="<?= $value ?>">
                                    <div class="switch-btn va-m <?= $switch ?>" data-input="<?= $propId ?>"></div>

                                <?php } elseif($arProp['TYPE'] === 'textarea') { ?>

                                    <textarea name="<?= $propId ?>"
                                              cols="86"
                                              rows="8"
                                    ><?= $value ?></textarea>

                                <?php } elseif($arProp['TYPE'] === 'info') { ?>

                                    <div align="left" class="adm-info-message-wrap">
                                        <div class="adm-info-message">
                                            <?=htmlspecialchars_decode($arProp['TEXT'])?>
                                        </div>
                                    </div>

                                <?php } ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php }
        }

        $tabControl->Buttons();
        $tabControl->ShowWarnings("post_form", '');
        ?>

        <input class="adm-btn-save" type="submit" name="save" value="Сохранить">
        <input type="button" onclick="location.reload()" value="Отменить" />

        <?php $tabControl->End(); ?>
    </form>


    <style>
        /*Переключатель*/
        .switch-btn {
            display: inline-block;
            width: 34px;
            height: 20px;
            border-radius: 19px;
            background: #bfbfbf;
            z-index: 0;
            margin: 0;
            padding: 0;
            border: none;
            cursor: pointer;
            position: relative;
            transition-duration: 300ms;
        }
        .switch-btn::after {
            content: "";
            height: 14px;
            width: 14px;
            border-radius: 17px;
            background: #fff;
            top: 3px;
            left: 3px;
            transition-duration: 300ms;
            position: absolute;
            z-index: 1;
        }
        .switch-on {
            background: #4caf50;
        }
        .switch-on::after {
            left: 16px;
        }
        .gray {
            color: gray;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function (){
            //Toggle
            $(document).on('click', '.switch-btn', function(){
                let value = 'N';
                let inputName = $(this).data('input');

                $(this).toggleClass('switch-on');

                if($(this).hasClass('switch-on')){
                    value = 'Y';
                }

                $('input[name="'+inputName+'"]').val(value);
            });
        });
    </script>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
