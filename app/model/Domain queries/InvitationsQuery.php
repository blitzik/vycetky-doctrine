<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Nette\Utils\Validators;
use Kdyby;

class InvitationsQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];

    public function byId($id)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('i.id = :id')->setParameter('id', $id);
        };

        return $this;
    }

    public function byEmail($email)
    {
        Validators::assert($email, 'email');

        $this->filter[] = function (QueryBuilder $qb) use ($email) {
            $qb->andWhere('i.email = :email')->setParameter('email', $email);
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
        $qb = (new QueryBuilder($repository->getEntityManager()))
              ->select('i')
              ->from(Invitation::class, 'i');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}