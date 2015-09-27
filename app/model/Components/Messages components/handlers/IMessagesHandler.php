<?php

namespace App\Model\MessagesHandlers;

interface IMessagesHandler
{
    /**
     * @return string
     */
    public function getMessagesType();

    /**
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function getResultSet();

    /**
     * @param $messageID
     * @return void
     */
    public function removeMessage($messageID);

    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs);
}