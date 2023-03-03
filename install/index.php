<?php

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use RDN\Error\Entities\Internals\LogTable;

class rdn_error extends \CModule
{
    public const MODULE_ID = 'rdn.error';

    public $MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $this->MODULE_ID = self::MODULE_ID;

        $this->MODULE_VERSION = $this->getVersion();
        $this->MODULE_VERSION_DATE = $this->getVersionDate(date('Y-m-d H:i:s'));

        $this->MODULE_NAME = '[RDN] Error';
        $this->MODULE_DESCRIPTION = '';

        $this->PARTNER_NAME = 'RDN';
        $this->PARTNER_URI = '';
    }

    public function DoInstall()
    {
        try {
            $this->installDB();
            $this->InstallEvents();
            $this->InstallFiles();

            ModuleManager::registerModule($this->MODULE_ID);
        } catch (\Exception $e) {
            $this->errorHandler($e->getMessage());
            return false;
        }
    }

    public function DoUninstall()
    {
        try {
            $this->UnInstallDB();
            $this->UnInstallEvents();
            $this->UnInstallFiles();

            ModuleManager::unRegisterModule($this->MODULE_ID);
        } catch (\Exception $e) {
            $this->errorHandler($e->getMessage());
            return false;
        }
    }


    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public function installDB(): bool
    {
        $moduleDir = realpath(__DIR__.'/..');

        @include $moduleDir.'/lib/Entities/LongtextField.php';
        @include $moduleDir.'/lib/Entities/Internals/LogTable.php';

        $connection = Application::getConnection();
        $tableName = LogTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            Base::getInstance(LogTable::class)->createDbTable();

            $connection->queryExecute("ALTER TABLE {$tableName} MODIFY COLUMN `DUMP` LONGTEXT COLLATE 'utf8_general_ci' NULL;");
            $connection->queryExecute("ALTER TABLE {$tableName} CONVERT TO CHARACTER SET utf8, COLLATE utf8_general_ci;");
        }

        return true;
    }

    /**
     * @throws SqlQueryException
     * @throws LoaderException
     */
    public function UnInstallDB(): bool
    {
        Loader::includeModule(self::MODULE_ID);

        $connection = Application::getConnection();
        $tableName = LogTable::getTableName();

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }

        return true;
    }

    public function getEvents(): array
    {
        return [
            [
                'MODULE' => 'main',
                'EVENT' => 'OnBuildGlobalMenu',
                'CLASS' => '\RDN\Error\Admin\Menu',
                'METHOD' => 'buildMenu'
            ],
        ];
    }

    public function InstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        if (!empty($arEvents = $this->getEvents())) {
            foreach ($arEvents as $arEvent) {
                $eventManager->registerEventHandler(
                    $arEvent['MODULE'],
                    $arEvent['EVENT'],
                    $this->MODULE_ID,
                    $arEvent['CLASS'],
                    $arEvent['METHOD']
                );
            }
        }

        return true;
    }

    public function UnInstallEvents(): bool
    {
        $eventManager = EventManager::getInstance();

        if (!empty($arEvents = $this->getEvents())) {
            foreach ($arEvents as $arEvent) {
                $eventManager->unregisterEventHandler(
                    $arEvent['MODULE'],
                    $arEvent['EVENT'],
                    $this->MODULE_ID,
                    $arEvent['CLASS'],
                    $arEvent['METHOD']
                );
            }
        }

        return true;
    }

    public function InstallFiles()
    {
        $this->createAdminFiles();
    }

    public function UnInstallFiles()
    {
        $this->removeAdminFiles();
    }


    ########[ HELPERS ]######################################################################################

    private function errorHandler(string $message): void
    {
        global $APPLICATION;
        $APPLICATION->ThrowException($message);
    }

    private function getVersion(): ?string
    {
        $arModuleVersion = $this->getModuleVersion();
        return ($arModuleVersion['VERSION']) ?: '1.0.0';
    }

    private function getVersionDate($default = ''): ?string
    {
        $arModuleVersion = $this->getModuleVersion();
        return ($arModuleVersion['VERSION_DATE']) ?: $default;
    }

    private function getModuleVersion(): array
    {
        static $arModuleVersion = [];
        require_once dirname(__FILE__).'/version.php';
        return $arModuleVersion;
    }

    /**
     * Функция создает нужные файлы в папке /bitrix/admin/
     * со ссылкой на файлы модуля в папке admin
     */
    private function createAdminFiles(): void
    {
        if (!empty($files = $this->getAdminFiles())) {
            foreach ($files as $fileName => $fileFullPath) {
                $fileRelPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $fileFullPath);
                $content = '<?php require $_SERVER["DOCUMENT_ROOT"] . "'.$fileRelPath.'";';

                file_put_contents(
                    $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$fileName,
                    str_replace('"', '\'', $content)
                );
            }
        }
    }

    /**
     * Функция удаляет файлы модуля из папке /bitrix/admin/
     */
    private function removeAdminFiles(): void
    {
        if (!empty($files = $this->getAdminFiles())) {
            foreach ($files as $fileName => $fileFullPath) {
                if (is_file($file = $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$fileName)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Возвращает массив файлов модуля (с полными путями) для админки
     *
     * @return array
     */
    private function getAdminFiles(): array
    {
        $arFiles = [];

        $dirPath = dirname(__FILE__, 2).'/admin';
        $dirSource = opendir($dirPath);

        while ($fileName = readdir($dirSource)) {
            $filePath = $dirPath.'/'.$fileName;
            if (is_file($filePath)) {
                $arFiles[$fileName] = $filePath;
            }
        }

        return $arFiles;
    }
}
