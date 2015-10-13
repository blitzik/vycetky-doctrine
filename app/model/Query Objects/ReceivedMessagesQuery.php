<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\ReceivedMessage;
use App\Model\Domain\Entities\User;
use Exceptions\Logic\InvalidArgumentException;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby;
use Nette\Utils\Validators;

class ReceivedMessagesQuery extends QueryObject
{
    /** @var array|\Closure[] */
    private $filter = [];

    /** @var array|\Closure[] */
    private $select = [];


    /* INTERNAL properties */

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var User */
    private $recipient;

    /** @var ReceivedMessage */
    private $message;

    /** @var bool */
    private $isLookingForRead;
    private $isMessageJoined;


    public function includingMessage(array $fields = null)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $this->joinMessage($qb);

            if (isset($fields) and !empty($fields)) {
                $parts = implode(',', $fields);
                $qb->addSelect('partial m.{' .$parts. '}');
            } else {
                $qb->addSelect('m');
            }
        };

        return $this;
    }

    public function includingMessageAuthor(array $fields = null)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $this->joinMessage($qb);

            $qb->leftJoin('m.author', 'a');

            if (isset($fields) and !empty($fields)) {
                $parts = implode(',', $fields);
                $qb->addSelect('partial a.{' .$parts. '}');
            } else {
                $qb->addSelect('a');
            }
        };

        return $this;
    }

    public function includingRecipient(array $fields = null)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $qb->innerJoin('rm.recipient', 'r');

            if (isset($fields) and !empty($fields)) {
                $parts = implode(',', $fields);
                $qb->addSelect('partial r.{' .$parts. '}');
            } else {
                $qb->addSelect('r');
            }
        };

        return $this;
    }

    public function findReadMessages()
    {
        $this->isLookingForRead = true;

        $this->filter[] = function(QueryBuilder $qb) {
            $this->joinMessage($qb);
            $qb->addSelect('m');
            $qb->andWhere('rm.read = 1');
        };

        return $this;
    }

    public function findUnreadMessages()
    {
        $this->isLookingForRead = false;

        $this->filter[] = function(QueryBuilder $qb) {
            $this->joinMessage($qb);
            $qb->addSelect('m');
            $qb->andWhere('rm.read = 0');
        };

        return $this;
    }

    public function byRecipient(User $recipient)
    {
        $this->filter[] = function(QueryBuilder $qb) use ($recipient) {
            $qb->andWhere('rm.recipient = :recipient')
               ->setParameter('recipient', $recipient);
        };

        return $this;
    }

    /**
     * @param SentMessage|int $message
     * @return $this
     */
    public function byMessage($message)
    {
        if (!Validators::is($message, 'numericint') and
            !($message instanceof SentMessage)) {
            throw new InvalidArgumentException(
                'Argument $message must be integer number or
                 instance of ' . SentMessage::class
            );
        }

        $this->message = $message;

        $this->filter[] = function (QueryBuilder $qb) use ($message) {
            $qb->andWhere('rm.message = :message')
               ->setParameter('message', $message);
        };

        return $this;
    }

    public function onlyActive()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('rm.deleted = 0');
        };

        return $this;
    }

    private function joinMessage(QueryBuilder $qb)
    {
        if (!isset($this->isMessageJoined) or $this->isMessageJoined === false) {
            $this->isMessageJoined = true;

            $qb->innerJoin('rm.message', 'm');
        }
    }

    /**
     * @param Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
    {
        $this->queryBuilder
             ->resetDQLParts(['select', 'orderBy', 'join'])
             ->select('COUNT(rm.id) as total_count');

        return $this->queryBuilder;
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
              ->select('rm')
              ->from(ReceivedMessage::class, 'rm')
              ->orderBy('rm.id', 'DESC');

        foreach ($this->filter as $modifier) {
            $modifier($this->queryBuilder);
        }

        return $this->queryBuilder;
    }

}