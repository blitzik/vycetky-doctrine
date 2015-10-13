<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\SentMessagesQuery;

class SentMessagesHandler extends MessagesHandler implements IMessagesHandler
{
    /**
     * @var SentMessagesQuery
     */
    private $query;

    public function __construct(
        User $user,
        MessagesFacade $messagesFacade
    ) {
        parent::__construct($user, $messagesFacade);

        $this->query = new SentMessagesQuery();
        $this->query->withAuthor(['id', 'username', 'role'])
                    ->byAuthor($user)
                    ->onlyActive()
                    ->withoutSystemMessages();
    }

    /**
     * @return string
     */
    public function getMessagesType()
    {
        return SentMessage::SENT;
    }

    /**
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function getResultSet()
    {
        return $this->messagesFacade->fetchSentMessages($this->query);
    }


    /**
     * @param $messageID
     * @return void
     */
    public function removeMessage($messageID)
    {
        $this->messagesFacade->removeMessages([$messageID]);
    }

    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs)
    {
        $this->messagesFacade->removeMessages($messagesIDs);
    }
}