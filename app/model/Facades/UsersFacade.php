<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Services\Readers\InvitationsReader;
use App\Model\Services\Readers\UsersReader;
use App\Model\Services\Users\UserSystemCreator;
use App\Model\Services\UsersService;
use App\Model\Services\Writers\UsersWriter;
use Exceptions\Runtime\DuplicateEmailException;
use Exceptions\Runtime\DuplicateUsernameException;
use Exceptions\Runtime\InvalidUserInvitationEmailException;
use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\UserNotFoundException;
use Kdyby\Persistence\Query;
use Nette\Object;
use Nette\Utils\Arrays;

class UsersFacade extends Object
{
    /** @var UsersReader  */
    private $usersReader;

    /** @var UsersWriter  */
    private $usersWriter;

    /** @var UsersService  */
    private $usersService;

    /** @var InvitationsReader  */
    private $invitationsReader;

    /** @var UserSystemCreator  */
    private $userSystemCreator;

    public function __construct(
        UsersReader $usersReader,
        UsersWriter $usersWriter,
        UsersService $usersService,
        InvitationsReader $invitationsReader,
        UserSystemCreator $userSystemCreator
    ) {
        $this->usersReader = $usersReader;
        $this->usersWriter = $usersWriter;
        $this->usersService = $usersService;
        $this->invitationsReader = $invitationsReader;
        $this->userSystemCreator = $userSystemCreator;
    }

    /**
     * @param User $user
     * @return User
     */
    public function saveUser(User $user)
    {
        return $this->usersWriter->saveUser($user);
    }

    /**
     * @param Query $usersQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchUsers(Query $usersQuery)
    {
        return $this->usersReader->fetchUsers($usersQuery);
    }

    /**
     * @param $id
     * @return User|null
     */
    public function getUserByID($id)
    {
        return $this->usersReader->getUserByID($id);
    }

    /**
     * @param $email
     * @return User|null
     */
    public function getUserByEmail($email)
    {
        return $this->usersReader->getUserByEmail($email);
    }

    /**
     * @param bool $onlyActiveUsers
     * @param array|null $except
     * @return array
     */
    public function findAllUsers($onlyActiveUsers = false, array $except = null)
    {
        $users = Arrays::associate($this->usersReader->findAllUsers($onlyActiveUsers), 'id');

        $users = (isset($except) ? array_diff_key($users, $except) : $users);

        return $this->usersService->separateSuspendedUsers($users);
    }

    /**
     * @return array
     */
    public function findSuspendedUsers()
    {
        return Arrays::associate($this->usersReader->findSuspendedUsers(), 'id');
    }

    /**
     * @param array $usersIDs
     * @return array
     */
    public function findUsers(array $usersIDs)
    {
        return $this->usersReader->findUsersByIDs($usersIDs);
    }

    /**
     * Finds users that are blocked by or blocking given user
     *
     * @param User $user
     * @return array
     * @throws UserNotFoundException
     */
    public function findRestrictedUsers(User $user)
    {
        $result = $this->usersReader->getUserWithRestrictedRelationships($user);
        if (empty($result)) {
            return $result;
        }

        $resultArray = [
            'usersBlockedByMe' => Arrays::associate($result[0]['usersBlockedByMe'], 'id'),
            'usersBlockingMe'  => Arrays::associate($result[0]['usersBlockingMe'], 'id')
        ];

        return /*$this->usersService->mergeUsersBlockingEachOther(*/$resultArray/*)*/;
    }

    /**
     * @param User $user
     * @param Invitation $invitation
     * @return User
     * @throws InvalidUserInvitationEmailException
     * @throws InvitationNotFoundException
     * @throws InvitationExpiredException
     * @throws DuplicateEmailException
     * @throws DuplicateUsernameException
     */
    public function registerNewUser(User $user, Invitation $invitation)
    {
        if ($user->email !== $invitation->email) {
            throw new InvalidUserInvitationEmailException;
        }

        if (!$invitation->isActive()) {
            throw new InvitationExpiredException;
        }

        return $this->userSystemCreator->registerUser($user, $invitation);
    }
    
    /**
     * @param $email
     * @return User
     * @throws UserNotFoundException
     */
    public function createPasswordRestoringToken($email)
    {
        $user = $this->usersReader->getUserByEmail($email);
        if ($user === null) {
            throw new UserNotFoundException;
        }

        $user->createToken();
        return $this->saveUser($user);
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getTotalWorkedStatistics(User $user)
    {
        return $this->usersReader->getTotalWorkedStatistics($user);
    }
}