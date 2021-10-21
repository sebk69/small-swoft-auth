<?php

/**
 * This file is a part of sebk/small-swoft-auth
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallSwoftAuth\Service\Form;

use Sebk\SmallOrmForms\Form\AbstractForm;
use Sebk\SmallOrmForms\Form\Field;

class LoginForm extends AbstractForm
{
    const ACCOUNT_KEY = 'account';
    const PASSWORD_KEY = 'password';

    public function __construct(array $keys, array $values)
    {
        $this->addField($keys[self::ACCOUNT_KEY], null, $values[$keys[self::ACCOUNT_KEY]] ?? null, null, Field::MANDATORY);
        $this->addField($keys[self::PASSWORD_KEY], null, $values[$keys[self::PASSWORD_KEY]] ?? null, null, Field::MANDATORY);
    }
}
