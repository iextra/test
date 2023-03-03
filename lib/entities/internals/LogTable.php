<?php

namespace RDN\Error\Entities\Internals;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config;
use RDN\Error\HashStorage;
use RDN\Error\Notification\AdminSection;
use RDN\Error\Notification\Email;
use RDN\Error\Notification\Telegram;
use RDN\Error\Entities;
use RDN\Error\Entities\LongtextField;
use RDN\Error\Option;

class LogTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'rdn_error_log';
    }

    public static function getObjectClass(): string
    {
        return Entities\Log::class;
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new TextField('MESSAGE'))
                ->configureNullable(),

            (new TextField('ERROR_LEVEL'))
                ->configureNullable(),

            (new TextField('SECTION'))
                ->configureNullable(),

            (new StringField('ENTITY'))
                ->configureNullable(),

            (new StringField('FUNCTION'))
                ->configureNullable(),

            (new StringField('CALLED_FROM'))
                ->configureNullable(),

            (new LongtextField('DUMP'))
                ->configureNullable(),

            (new StringField('CHECK_SUM'))
                ->configureNullable(),

            (new DatetimeField('DATE_CREATE'))
                ->configureDefaultValue((new DateTime())),
        ];
    }

    public static function onBeforeAdd(Event $event): EventResult
    {
        $moduleId = 'rdn.error';

        /** @var Entities\Log $entity */
        $entity = $event->getParameter('object');

        if (
            ! empty($sites = Config\Option::get($moduleId, Option::AVAILABLE_SITES))
            && ! empty($serverName = Config\Option::get('main', 'server_name'))
        ) {
            $arSites = array_map(function ($value) {
                return trim(str_replace(['http::', 'https::', '/'], '', $value));
            }, explode(',', $sites));

            if (! in_array($serverName, $arSites)) {
                return new EventResult();
            }
        }

        if (Config\Option::get($moduleId, Option::ENABLED_ADMIN_SECTION_NOTICE, 'N') == 'Y') {
            AdminSection::send();
        }

        if (Config\Option::get($moduleId, Option::ENABLED_EMAIL_NOTICE, 'N') == 'Y') {
            Email::send($entity->getMessage());
        }

        if (Config\Option::get($moduleId, Option::ENABLED_TG_NOTICE, 'N') == 'Y') {
            Telegram::send($entity->getMessage());
        }

        return new EventResult();
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterDelete(Event $event)
    {
        $collection = self::query()
            ->setSelect(['CHECK_SUM'])
            ->exec()
            ->fetchCollection();

        $hashManager = new HashStorage(dirname(__DIR__, 6));
        $hashManager->clear();

        if ($collection->count() > 0) {
            $hashManager->addMany($collection->getCheckSumList());
        }
    }
}
