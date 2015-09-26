<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Query\UsersQuery;
use App\Model\Services\Managers\UsersManager;
use App\Model\Services\Readers\UsersReader;
use Exceptions\Runtime\UserNotFoundException;
use Nette\Object;

class UsersFacade extends Object
{
    /**
     * @var UsersManager
     */
    private $usersManager;

    /**
     * @var UsersReader
     */
    private $usersReader;

    public function __construct(
        UsersManager $usersManager,
        UsersReader $usersReader
    ) {
        $this->usersManager = $usersManager;
        $this->usersReader = $usersReader;
    }

    /**
     * @param User $user
     * @return User
     */
    public function saveUser(User $user)
    {
        return $this->usersManager->saveUser($user);
    }

    /**
     * @param UsersQuery $usersQuery
     * @return User|null
     */
    public function fetchUser(UsersQuery $usersQuery)
    {
        return $this->usersReader->fetchUser($usersQuery);
    }

    /**
     * @param UsersQuery $usersQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchUsers(UsersQuery $usersQuery)
    {
        return $this->usersReader->fetchUsers($usersQuery);
    }

    /**
     * @param User $user
     * @param Invitation $invitation
     * @return User
     */
    public function registerNewUser(User $user, Invitation $invitation)
    {
        return $this->usersManager->registerNewUser($user, $invitation);
    }
    
    /**
     * @param $email
     * @return User
     * @throws UserNotFoundException
     */
    public function createPasswordRestoringToken($email)
    {
        return $this->usersManager->createPasswordRestoringToken($email);
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getTotalWorkedStatistics(User $user)
    {
        return $this->usersManager->getTotalWorkedStatistics($user);
    }
}