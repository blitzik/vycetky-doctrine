<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\Message;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\MessagesQuery;

class SentMessagesHandler extends MessagesHandler implements IMessagesHandler
{
    /**
     * @var MessagesQuery
     */
    private $query;

    public function __construct(
        User $user,
        MessagesFacade $messagesFacade
    ) {
        parent::__construct($user, $messagesFacade);

        $this->query = new MessagesQuery();
        $this->query->byAuthor($user);
    }

    /**
     * @return string
     */
    public function getMessagesType()
    {
        return Message::SENT;
    }

    /**
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function getResultSet()
    {
        return $this->messagesFacade->fetchMessages($this->query);
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