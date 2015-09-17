<?php

namespace MessagesLoaders;

use App\Model\Entities\Message;

class ReceivedReadMessagesLoader extends MessagesLoader implements IMessagesLoader
{
    /**
     * @return string
     */
    public function getMessagesType()
    {
        return Message::RECEIVED;
    }

    /**
     * @return int
     */
    public function getNumberOfMessages()
    {
        return $this->messageFacade
                    ->getNumberOfReceivedMessages(Message::READ);
    }

    /**
     * @param $offset
     * @param $length
     * @return array Array of MessagesUser Entities or empty array
     */
    public function findMessages($offset, $length)
    {
        return $this->messageFacade
                    ->findReceivedMessages(Message::READ, $offset, $length);
    }

    /**
     * @param $messageID
     * @return void
     */
    public function removeMessage($messageID)
    {
        $this->messageFacade->removeMessage($messageID, $this->getMessagesType());
    }

    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs)
    {
        $this->messageFacade->removeMessages($messagesIDs, $this->getMessagesType());
    }
}