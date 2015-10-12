<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;
use Kdyby;

class InvitationsQuery extends QueryObject
{
    /** @var array|\Closure[] */
    private $filter = [];

    /** @var array|\Closure[] */
    private $select = [];

    /** @var QueryBuilder */
    private $queryBuilder;

    public function onlyWithFields(array $fields)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $qb->resetDQLPart('select');

            $parts = implode(',', $fields);
            $qb->select('partial i.{' .$parts. '}');
        };

        return $this;
    }

    public function bySender(User $sender)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($sender) {
            $qb->andWhere('i.sender = :sender')->setParameter('sender', $sender);
        };

        return $this;
    }

    public function onlyActive()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('i.validity > CURRENT_DATE()');
        };

        return $this;
    }

    /**
     * @param Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateCountQuery(Queryable $repository)
    {
        if (isset($this->queryBuilder)) {
            $qb = $this->queryBuilder;
        } else {
            $qb = $this->createBasicDql($repository);
        }

        $qb->resetDQLPart('select');
        $qb->select('COUNT(i.email) AS total_count');

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
        $this->queryBuilder = (new QueryBuilder($repository->getEntityManager()))
                              ->select('i')
                              ->from(Invitation::class, 'i');

        foreach ($this->filter as $modifier) {
            $modifier($this->queryBuilder);
        }

        return $this->queryBuilder;
    }

}