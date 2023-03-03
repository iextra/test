<?php

namespace RDN\Error\Entities;

use Bitrix\Main\Entity\TextField;

class LongtextField extends TextField
{
    public function convertValueFromDb($value): string
    {
        return $this->getConnection()->getSqlHelper()->convertFromDbText($value);
    }

    public function convertValueToDb($value): ?string
    {
        return $this->getConnection()->getSqlHelper()->convertFromDbText($value);
    }
}
