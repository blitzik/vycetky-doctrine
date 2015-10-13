<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\ReceivedMessagesQuery;

class ReceivedReadMessagesHandler extends MessagesHandler implements IMessagesHandler
{
    /**
     * @var ReceivedMessagesQuery
     */
    private $query;

    public function __construct(
        User $user,
        MessagesFacade $messagesFacade
    ) {
        parent::__construct($user, $messagesFacade);

        $this->query = new ReceivedMessagesQuery();
        $this->query->byRecipient($user)
                    ->onlyActive()
                    ->findReadMessages()
                    ->includingMessageAuthor(['id', 'username', 'role']);
    }

    /**
     * @return string
     */
    public function getMessagesType()
    {
        return SentMessage::RECEIVED;
    }

    /**
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function getResultSet()
    {
        return $this->messagesFacade->fetchReceivedMessages($this->query);
    }

    /**
     * @param $messageID
     * @return void
     */
    public function removeMessage($messageID)
    {
        $this->messagesFacade->removeMessagesReferences([$messageID]);
    }

    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs)
    {
        $this->messagesFacade->removeMessagesReferences($messagesIDs);
    }
}