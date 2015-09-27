<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby;

class MessagesQuery extends QueryObject
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
     * @var User
     */
    private $author;

    /**
     * @var bool
     */
    private $isLookingForDeleted;


    public function withAuthor(array $fields = null)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $qb->innerJoin('m.author', 'a');

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
            $qb->andWhere('m.id = :id')
                ->setParameter('id', $id);
        };

        return $this;
    }

    public function onlyActive()
    {
        $this->isLookingForDeleted = false;

        $this->filter[] = function(QueryBuilder $qb) {
            $qb->andWhere('m.deleted = 0');
        };

        return $this;
    }

    public function byAuthor(User $author)
    {
        $this->author = $author;

        $this->filter[] = function(QueryBuilder $qb) use ($author) {
            $qb->andWhere('m.author = :author')
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
        $qb = new QueryBuilder($repository->getEntityManager());
        $qb->select('COUNT(m.id) as total_count')
           ->from(Message::class, 'm');

        if (isset($this->author)) {
            $qb->andWhere('m.author = :author')
               ->setParameter('author', $this->author);
        }

        if ($this->isLookingForDeleted === false) {
            $qb->andWhere('m.deleted = 0');
        }

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
        $qb = (new QueryBuilder($repository->getEntityManager()))
              ->select('m')
              ->from(Message::class, 'm');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}