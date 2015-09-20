<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Query\UsersQuery;
use App\Model\Services\Managers\InvitationsManager;
use App\Model\Services\Managers\UsersManager;
use App\Model\Services\Readers\UsersReader;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Exceptions\Runtime\UserNotFoundException;
use Nette\Object;

class UsersFacade extends Object
{
    /**
     * @var InvitationsManager
     */
    private $invitationsManager;

    /**
     * @var UsersManager
     */
    private $usersManager;

    /**
     * @var UsersReader
     */
    private $usersReader;

    public function __construct(
        InvitationsManager $invitationsManager,
        UsersManager $usersManager,
        UsersReader $usersReader
    ) {
        $this->invitationsManager = $invitationsManager;
        $this->usersManager = $usersManager;
        $this->usersReader = $usersReader;
    }

    /**
     * @param UsersQuery $usersQuery
     * @return mixed
     * @throws UserNotFoundException
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
     * @param $email
     * @return Invitation
     * @throws InvitationAlreadyExistsException
     * @throws UserAlreadyExistsException
     */
    public function createInvitation($email)
    {
        return $this->invitationsManager->createInvitation($email);
    }

    /**
     * @param string $email
     * @param string $token
     * @return \App\Model\Domain\Entities\Invitation
     * @throws InvitationNotFoundException
     * @throws InvitationExpiredException
     */
    public function checkInvitation($email, $token)
    {
        return $this->invitationsManager->checkInvitation($email, $token);
    }

    /**
     * @param Invitation $invitation
     */
    public function removeInvitation(Invitation $invitation)
    {
        $this->invitationsManager->removeInvitation($invitation);
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
     * @param User $user
     * @return mixed
     */
    public function getTotalWorkedStatistics(User $user)
    {
        return $this->usersManager->getTotalWorkedStatistics($user);
    }
}