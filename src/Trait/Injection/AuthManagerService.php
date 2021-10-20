<?php

/**
 * This file is a part of sebk/small-swoft-auth
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallSwoftAuth\Trait\Injection;

use Swoft\Bean\Annotation\Mapping\Inject;

trait AuthManagerService
{
    /**
     * @Inject ()
     * @var \Sebk\SmallSwoftAuth\Service\AuthManagerService
     */
    protected $authManager;
}
