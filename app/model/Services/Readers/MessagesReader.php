<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\ReceivedMessage;
use App\Model\Query\ReceivedMessagesQuery;
use App\Model\Query\SentMessagesQuery;
use \Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class MessagesReader extends Object
{
    /** @var EntityManager  */
    private $em;

    /** @var EntityRepository  */
    private $messageRepository;

    /** @var EntityRepository  */
    private $messageReferenceRepository;


    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;

        $this->messageRepository = $this->em->getRepository(SentMessage::class);
        $this->messageReferenceRepository = $this->em->getRepository(ReceivedMessage::class);
    }


    /**
     * @param SentMessagesQuery $query
     * @return SentMessage|null
     */
    public function fetchMessage(SentMessagesQuery $query)
    {
        return $this->messageRepository->fetchOne($query);
    }


    /**
     * @param SentMessagesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchMessages(SentMessagesQuery $query)
    {
        return $this->messageRepository->fetch($query);
    }


    /**
     * @param ReceivedMessagesQuery $query
     * @return ReceivedMessage|null
     */
    public function fetchMessageReference(ReceivedMessagesQuery $query)
    {
        return $this->messageReferenceRepository->fetchOne($query);
    }


    /**
     * @param ReceivedMessagesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchMessagesReferences(ReceivedMessagesQuery $query)
    {
        return $this->messageReferenceRepository->fetch($query);
    }


    /**
     * @param $id
     * @return SentMessage|null
     */
    public function getSentMessage($id)
    {
        return $this->em->createQuery(
            'SELECT sm, partial a.{id, username, role} FROM ' .SentMessage::class. ' sm
             LEFT JOIN sm.author a
             WHERE sm.id = :id'
        )->setParameter('id', $id)
         ->getOneOrNullResult();
    }


    /**
     * @param $id
     * @return ReceivedMessage|null
     */
    public function getReceivedMessage($id)
    {
        return $this->em->createQuery(
            'SELECT rm, m, partial a.{id, username, role} FROM ' .ReceivedMessage::class. ' rm
             JOIN rm.message m
             LEFT JOIN m.author a
             WHERE rm.id = :id'
        )->setParameter('id', $id)
         ->getOneOrNullResult();
    }


    /**
     * @param $messageID
     * @return array
     */
    public function findReceivedMessages($messageID)
    {
        return $this->em->createQuery(
            'SELECT rm, partial r.{id, username} FROM ' .ReceivedMessage::class. ' rm
             JOIN rm.recipient r
             WHERE rm.message = :messageID'
        )->setParameter('messageID', $messageID)
         ->getArrayResult();
    }
}