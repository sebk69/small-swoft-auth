<?php

/**
 * This file is a part of sebk/small-swoft-auth
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallSwoftAuth\Service;

use Swoft\Auth\AuthManager;
use Swoft\Auth\AuthSession;
use Swoft\Auth\Contract\AuthManagerInterface;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * @Bean ()
 */
class AuthManagerService extends AuthManager implements AuthManagerInterface
{

    const DAYS = 86400;

    // Setup cache
    protected $cacheClass = CacheManager::class;
    protected $cacheEnable = true;

    protected $sessionDuration = self::DAYS * 1;

    /**
     * Authenticate
     * @param array $data
     * @return AuthSession
     */
    public function auth(array $data): AuthSession
    {
        return $this->login(AuthLogic::class, $data);
    }

}
