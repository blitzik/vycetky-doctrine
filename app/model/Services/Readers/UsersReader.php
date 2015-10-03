<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Persistence\Query;
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
     * @param Query $usersQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchUsers(Query $usersQuery)
    {
        return $this->usersRepository->fetch($usersQuery);
    }

    /**
     * @param $email
     * @return bool
     */
    public function isEmailRegistered($email)
    {
        $count = $this->em->createQuery(
            'SELECT COUNT(u.id) FROM ' .User::class. ' u
             WHERE u.email = :email'
        )->setParameter('email', $email)->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param $username
     * @return bool
     */
    public function isUsernameRegistered($username)
    {
        $count = $this->em->createQuery(
            'SELECT COUNT(u.id) FROM ' .User::class. ' u
             WHERE u.username = :username'
        )->setParameter('username', $username)->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function getUserByID($id)
    {
        $query = $this->getBasicUserQuery()
                      ->where('u.id = :id')
                      ->setParameter('id', $id)
                      ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param $email
     * @return User|null
     */
    public function getUserByEmail($email)
    {
        $query = $this->getBasicUserQuery()
                      ->where('u.email = :email')
                      ->setParameter('email', $email)
                      ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getTotalWorkedStatistics(User $user)
    {
        $stats = $this->em
            ->createQuery(
                'SELECT SUM(time_to_sec(ADDTIME(SUBTIME(SUBTIME(wh.workEnd, wh.workStart), wh.lunch), wh.otherHours))) AS total_worked_hours,
                        COUNT(li.id) AS worked_days
                 FROM ' .Listing::class. ' l
                 LEFT JOIN ' .ListingItem::class. ' li WITH li.listing = l
                 LEFT JOIN li.workedHours wh
                 WHERE l.user = :user'
            )->setParameter('user', $user);

        return $stats->getSingleResult();
    }

    /**
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    private function getBasicUserQuery()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u');

        return $qb;
    }
}