<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby;

class SentMessagesQuery extends QueryObject
{
    /** @var array|\Closure[] */
    private $filter = [];

    /** @var array|\Closure[] */
    private $select = [];


    /** @var QueryBuilder */
    private $queryBuilder;


    public function withAuthor(array $fields = null)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $qb->innerJoin('sm.author', 'a');

            if (isset($fields) and !empty($fields)) {
                $parts = implode(',', $fields);
                $qb->addSelect('partial a.{' .$parts. '}');
            } else {
                $qb->addSelect('a');
            }
        };

        return $this;
    }

    public function byId($id)
    {
        $this->filter[] = function(QueryBuilder $qb) use ($id) {
            $qb->andWhere('sm.id = :id')
                ->setParameter('id', $id);
        };

        return $this;
    }

    public function onlyActive()
    {
        $this->filter[] = function(QueryBuilder $qb) {
            $qb->andWhere('sm.deleted = 0');
        };

        return $this;
    }

    public function withoutSystemMessages()
    {
        $this->filter[] = function(QueryBuilder $qb) {
            $qb->andWhere('sm.isSystemMessage = 0');
        };

        return $this;
    }

    public function byAuthor(User $author)
    {
        $this->filter[] = function(QueryBuilder $qb) use ($author) {
            $qb->andWhere('sm.author = :author')
               ->setParameter('author', $author);
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
        $qb->select('COUNT(sm.id) as total_count');

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
                              ->select('sm')
                              ->from(SentMessage::class, 'sm')
                              ->orderBy('sm.id', 'DESC');

        foreach ($this->filter as $modifier) {
            $modifier($this->queryBuilder);
        }

        return $this->queryBuilder;
    }

}