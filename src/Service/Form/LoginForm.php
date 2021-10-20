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

    public function __construct(array $values)
    {
        $this->addField(self::ACCOUNT_KEY, null, $values[self::ACCOUNT_KEY] ?? null, null, Field::MANDATORY);
        $this->addField(self::PASSWORD_KEY, null, $values[self::PASSWORD_KEY] ?? null, null, Field::MANDATORY);
    }
}
