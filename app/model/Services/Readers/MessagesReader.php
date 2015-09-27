<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\MessageReference;
use App\Model\Query\MessageReferencesQuery;
use App\Model\Query\MessagesQuery;
use \Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class MessagesReader extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $messageRepository;

    /**
     * @var EntityRepository
     */
    private $messageReferenceRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;

        $this->messageRepository = $this->em->getRepository(Message::class);
        $this->messageReferenceRepository = $this->em->getRepository(MessageReference::class);
    }

    /**
     * @param MessagesQuery $query
     * @return Message|null
     */
    public function fetchMessage(MessagesQuery $query)
    {
        return $this->messageRepository->fetchOne($query);
    }

    /**
     * @param MessagesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchMessages(MessagesQuery $query)
    {
        return $this->messageRepository->fetch($query);
    }

    /**
     * @param MessageReferencesQuery $query
     * @return MessageReference|null
     */
    public function fetchMessageReference(MessageReferencesQuery $query)
    {
        return $this->messageReferenceRepository->fetchOne($query);
    }

    /**
     * @param MessageReferencesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchMessagesReferences(MessageReferencesQuery $query)
    {
        return $this->messageReferenceRepository->fetch($query);
    }

}