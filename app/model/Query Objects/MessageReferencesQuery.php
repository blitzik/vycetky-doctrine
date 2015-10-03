<?php

namespace App\Model\Query;

use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\MessageReference;
use App\Model\Domain\Entities\User;
use Exceptions\Logic\InvalidArgumentException;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby;
use Nette\Utils\Validators;

class MessageReferencesQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];


    /* INTERNAL properties */

    /**
     * @var User
     */
    private $recipient;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var bool
     */
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

    public function includingRecipient(array $fields = null)
    {
        $this->select[] = function (QueryBuilder $qb) use ($fields) {
            $qb->innerJoin('mr.recipient', 'r');

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
            $qb->andWhere('mr.read = 1');
        };

        return $this;
    }

    public function findUnreadMessages()
    {
        $this->isLookingForRead = false;

        $this->filter[] = function(QueryBuilder $qb) {
            $this->joinMessage($qb);
            $qb->addSelect('m');
            $qb->andWhere('mr.read = 0');
        };

        return $this;
    }

    public function byRecipient(User $recipient)
    {
        $this->recipient = $recipient;

        $this->filter[] = function(QueryBuilder $qb) use ($recipient) {
            $qb->andWhere('mr.recipient = :recipient')
               ->setParameter('recipient', $recipient);
        };

        return $this;
    }

    /**
     * @param Message|int $message
     * @return $this
     */
    public function byMessage($message)
    {
        if (!Validators::is($message, 'numericint') and
            !($message instanceof Message)) {
            throw new InvalidArgumentException(
                'Argument $message must be integer number or
                 instance of ' . Message::class
            );
        }

        $this->message = $message;

        $this->filter[] = function (QueryBuilder $qb) use ($message) {
            $qb->andWhere('mr.message = :message')
               ->setParameter('message', $message);
        };

        return $this;
    }

    private function joinMessage(QueryBuilder $qb)
    {
        if (!isset($this->isMessageJoined) or $this->isMessageJoined === false) {
            $this->isMessageJoined = true;

            $qb->innerJoin('mr.message', 'm');
        }
    }

    /**
     * @param Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = new QueryBuilder($repository->getEntityManager());
        $qb->select('COUNT(mr.id) as total_count')
           ->from(MessageReference::class, 'mr');

        if (isset($this->recipient)) {
            $qb->where('mr.recipient = :recipient')
               ->setParameter('recipient', $this->recipient);
        }

        if (isset($this->message)) {
            $qb->where('mr.message = :message')
               ->setParameter('message', $this->message);
        }

        if ($this->isLookingForRead === true) {
            $qb->andWhere('mr.read = 1');
        }

        if ($this->isLookingForRead === false) {
            $qb->andWhere('mr.read = 0');
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
              ->select('mr')
              ->from(MessageReference::class, 'mr');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}