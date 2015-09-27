<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\MessageReference;
use App\Model\Query\MessageReferencesQuery;
use App\Model\Query\MessagesQuery;
use App\Model\Services\Managers\MessagesManager;
use App\Model\Services\Readers\MessagesReader;
use Nette\Object;

class MessagesFacade extends Object
{
    /**
     * @var MessagesManager
     */
    private $messagesManager;

    /**
     * @var MessagesReader
     */
    private $messagesReader;

    public function __construct(
        MessagesManager $messagesManager,
        MessagesReader $messagesReader
    ) {
        $this->messagesManager = $messagesManager;
        $this->messagesReader = $messagesReader;
    }

    /**
     * @param MessagesQuery $query
     * @return Message|null
     */
    public function fetchMessage(MessagesQuery $query)
    {
        return $this->messagesReader->fetchMessage($query);
    }

    /**
     * @param MessagesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchMessages(MessagesQuery $query)
    {
        return $this->messagesReader->fetchMessages($query);
    }

    /**
     * @param MessageReferencesQuery $query
     * @return MessageReference|null
     */
    public function fetchMessageReference(MessageReferencesQuery $query)
    {
        return $this->messagesReader->fetchMessageReference($query);
    }

    /**
     * @param MessageReferencesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchMessagesReferences(MessageReferencesQuery $query)
    {
        return $this->messagesReader->fetchMessagesReferences($query);
    }
}