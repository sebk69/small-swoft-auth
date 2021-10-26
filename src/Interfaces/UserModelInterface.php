<?php

/**
 * This file is a part of sebk/small-swoft-auth
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallSwoftAuth\Interfaces;

interface UserModelInterface
{
    /**
     * Return true if password match
     * @param string $password
     * @return bool
     */
    public function checkPassword(string $password);
}
