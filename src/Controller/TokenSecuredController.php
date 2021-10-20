<?php

/**
 * This file is a part of sebk/small-swoft-auth
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallSwoftAuth\Controller;

use App\Security\AccessDeniedException;
use App\Security\AuthFailedException;
use App\Security\AuthManagerService;
use Sebk\SwoftVoter\VoterManager\VoterInterface;
use Sebk\SwoftVoter\VoterManager\VoterManagerInterface;

abstract class TokenSecuredController
{
    /**
     * @var AuthManagerService
     */
    protected $authManager;

    /**
     * @var VoterManager
     */
    protected $voterManager;

    public function __construct()
    {
        $this->authManager = bean(AuthManagerService::class);
        $this->voterManager = bean(VoterManagerInterface::class);
    }

    /**
     * Get session extended data
     * @return array
     * @throws \Swoft\Exception\SwoftException
     */
    private function getData()
    {
        return $this->authManager->getSession()->getExtendedData();
    }

    /**
     * Return true if user logged in
     * @return bool
     * @throws \Swoft\Exception\SwoftException
     */
    protected function isLoggedIn()
    {
        return $this->authManager->getSession() != null && $this->authManager->getSession()->getIdentity() != null;
    }

    /**
     * Throw exception if not logged in
     * @throws AuthFailedException
     */
    protected function authFirewall()
    {
        if (!$this->isLoggedIn()) {
            throw new AuthFailedException("Auth failed");
        }
    }

    /**
     * Get user
     * @return \stdClass
     * @throws AuthFailedException
     * @throws \Swoft\Exception\SwoftException
     */
    protected function getUser()
    {
        $this->authFirewall();

        return bean('sebk_small_orm_dao')->get(...config('app.userDao'))->makeModelFromStdClass($this->getData()["user"]);
    }

    /**
     * Deny access if vote not granted
     * @param $attributes
     * @param \stdClass $subject
     * @throws AccessDeniedException
     * @throws AuthFailedException
     * @throws \Swoft\Exception\SwoftException
     */
    protected function denyAccessUnlessGranted($attributes, $subject)
    {
        // If single value for attribute, we transform it to array
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        // Vote
        if ($this->voterManager->vote($this->getUser(), $subject, $attributes) != VoterInterface::ACCESS_GRANTED) {
            // And deny access if not granted
            throw new AccessDeniedException("Forbidden access");
        }
    }
}
