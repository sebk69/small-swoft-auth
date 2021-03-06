<?php

/**
 * This file is a part of sebk/small-swoft-auth
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallSwoftAuth\Service;

use Sebk\SmallOrmSwoft\Traits\Injection\DaoFactory;
use Sebk\SmallSwoftAuth\Interfaces\UserModelInterface;
use Sebk\SmallSwoftAuth\Service\Form\LoginForm;
use Sebk\SmallOrmCore\Factory\DaoNotFoundException;
use Sebk\SmallOrmSwoft\Factory\Dao;
use Swoft\Auth\AuthResult;
use Swoft\Auth\Contract\AccountTypeInterface;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * @Bean ()
 */
class AuthLogic implements AccountTypeInterface
{
    // Inject dao factory
    use DaoFactory;

    /**
     * User configuration : app.user
     * Must contain :
     * - 'dao' => ['bundle of user dao', 'User model class name']
     * - 'accountField' => 'field name to match account login'
     * - 'identityField' => 'field name to match identity of user'
     * @var array
     */
    protected $userConfig;
    
    protected $loginForm;

    public function __construct()
    {
        // Get user config
        $this->userConfig = config('app.user');
        
        // Gets login fields
        $this->loginForm = config('app.loginForm');
    }

    /**
     * Login logic
     * @param array $data
     * @return AuthResult
     * @throws \Sebk\SmallOrmForms\Form\FieldNotFoundException
     */
    public function login(array $data): AuthResult
    {
        // Fail by default
        $failed = new AuthResult();

        try {
            // Check input
            $form = new LoginForm($this->loginForm, $data);
            $messages = $form->validate();
            if (count($messages) > 0) {
                return $failed;
            }

            // Get user
            $user = $this
                ->daoFactory
                ->get(...$this->userConfig['dao'])
                ->findOneBy([$this->userConfig['accountField'] => $form->getValue($this->loginForm[LoginForm::ACCOUNT_KEY])]);

            if (!$user instanceof UserModelInterface) {
                throw new \Exception("User (" . $this->userConfig['dao'][1] . ") model must implement UserModelInterface");
            }

            // Check password
            if (!$user->checkPassword($form->getValue($this->loginForm[LoginForm::PASSWORD_KEY]))) {
                return $failed;
            }

            // Successful login
            $success = new AuthResult();
            $success->setIdentity($user->getIdUtilisateur())
                ->setExtendedData([
                    "user" => $user,
                ]);
            return $success;
        } catch (DaoNotFoundException $e) {
        }

        // At this point login failed
        return $failed;
    }

    /**
     * Authenticate logic
     * @param string $identity
     * @return bool
     */
    public function authenticate(string $identity): bool
    {
        try {
            // User exists ?
            $this
                ->daoFactory
                ->get(...$this->userConfig['dao'])
                ->findOneBy([$this->userConfig['identityField'] => $identity]);
            return true;
        } catch (\Exception $e) {
        }

        // Anomaly => can't authenticate
        return false;
    }

}
