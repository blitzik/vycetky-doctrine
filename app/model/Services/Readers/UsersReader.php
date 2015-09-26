<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\User;
use App\Model\Query\UsersQuery;
use Exceptions\Runtime\UserNotFoundException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class UsersReader extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $usersRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->usersRepository = $this->em->getRepository(User::class);
    }

    /**
     * @param UsersQuery $usersQuery
     * @return User|null
     */
    public function fetchUser(UsersQuery $usersQuery)
    {
        return $this->usersRepository->fetchOne($usersQuery);
    }

    /**
     * @param UsersQuery $usersQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchUsers(UsersQuery $usersQuery)
    {
        return $this->usersRepository->fetch($usersQuery);
    }
}