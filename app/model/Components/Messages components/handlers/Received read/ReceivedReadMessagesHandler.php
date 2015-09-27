<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\MessageReferencesQuery;

class ReceivedReadMessagesHandler extends MessagesHandler implements IMessagesHandler
{
    /**
     * @var MessageReferencesQuery
     */
    private $query;

    public function __construct(
        User $user,
        MessagesFacade $messagesFacade
    ) {
        parent::__construct($user, $messagesFacade);

        $this->query = new MessageReferencesQuery();
        $this->query->findReadMessages()
                    ->includingMessageAuthor(['id', 'username', 'role'])
                    ->byRecipient($user);
    }

    /**
     * @return string
     */
    public function getMessagesType()
    {
        return Message::RECEIVED;
    }

    /**
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function getResultSet()
    {
        return $this->messagesFacade->fetchMessagesReferences($this->query);
    }

    /**
     * @param $messageID
     * @return void
     */
    public function removeMessage($messageID)
    {

    }

    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs)
    {

    }
}