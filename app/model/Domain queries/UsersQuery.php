<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\User;
use Exceptions\Logic\InvalidArgumentException;
use Kdyby\Doctrine\QueryObject;
use Nette\Utils\Validators;
use Kdyby;

class UsersQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];

    public function onlyWithFields(array $fields)
    {
        if (empty($fields)) {
            throw new InvalidArgumentException('Argument $fields must NOT be empty array!');
        }

        $this->select[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($fields) {
            $qb->resetDQLPart('select');

            $parts = implode(',', $fields);
            $qb->addSelect('partial u.{' .$parts. '}');
        };

        return $this;
    }

    public function findUsersBlockedByMe(User $user)
    {
        $this->select[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($user) {
            $qb->join('u.usersBlockingMe', 'f');
            $qb->andWhere('f.id = :id')
               ->setParameter('id', $user->getId());
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

    public function byUsername($username)
    {
        Validators::assert($username, 'unicode');

        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($username){
            $qb->andWhere('u.username = :username')->setParameter('username', $username);
        };

        return $this;
    }

    public function byEmail($email)
    {
        Validators::assert($email, 'email');

        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($email) {
            $qb->andWhere('u.email = :email')->setParameter('email', $email);
        };

        return $this;
    }

    public function byId($id)
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($id) {
            $qb->andWhere('u.id = :id')->setParameter('id', $id);
        };

        return $this;
    }

    /**
     * @param Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = new Kdyby\Doctrine\QueryBuilder($repository->getEntityManager());
        $qb->select('COUNT(u.id) as total_count')
           ->from(User::class, 'u');

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
     * @return Kdyby\Doctrine\NativeQueryBuilder
     */
    private function createBasicDql(Kdyby\Persistence\Queryable $repository)
    {
        $qb = (new Kdyby\Doctrine\QueryBuilder($repository->getEntityManager()))
             ->select('u')
             ->from(User::class, 'u');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}