<?php

namespace App\Model\Queries\Users;

use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryObject;
use Nette\Utils\Validators;
use Kdyby;

class UsersOverviewQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];


    /**
     * @var Kdyby\Doctrine\QueryBuilder
     */
    private $queryBuilder;


    public function onlyWithFields(array $fields)
    {
        $this->select[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($fields) {
            $qb->resetDQLPart('select');

            $parts = implode(',', $fields);
            $qb->addSelect('partial u.{' .$parts. '}');
        };

        return $this;
    }

    public function findUsersBlockedBy(User $user)
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($user) {
            $qb->join('u.usersBlockingMe', 'f');
            $qb->andWhere('f.id = :id')
               ->setParameter('id', $user->getId());
        };

        return $this;
    }

    public function withoutUser(User $user)
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($user){
            $qb->andWhere('u.id <> :id')->setParameter('id', $user->getId());
        };

        return $this;
    }

    public function likeUsername($username)
    {
        Validators::assert($username, 'unicode');

        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($username){
            $qb->andWhere('u.username LIKE :username')->setParameter('username', $username.'%');
        };

        return $this;
    }

    /**
     * @param Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
    {
        if (isset($this->queryBuilder)) {
            $qb = $this->queryBuilder;
        } else {
            $qb = $this->createBasicDql($repository);
        }

        $qb->resetDQLPart('select');
        $qb->select('COUNT(u.id) as total_count');

        return $qb;
    }

    /**
     * @param \Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $this->createBasicDql($repository);

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

    /**
     * @param Kdyby\Persistence\Queryable|Kdyby\Doctrine\EntityDao $repository
     * @return Kdyby\Doctrine\QueryBuilder
     */
    private function createBasicDql(Kdyby\Persistence\Queryable $repository)
    {
        $this->queryBuilder = (new Kdyby\Doctrine\QueryBuilder($repository->getEntityManager()))
                              ->select('u')
                              ->from(User::class, 'u');

        foreach ($this->filter as $modifier) {
            $modifier($this->queryBuilder);
        }

        return $this->queryBuilder;
    }

}