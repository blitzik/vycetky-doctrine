<?php

namespace MessagesLoaders;

interface IMessagesLoader
{
    /**
     * @return string
     */
    public function getMessagesType();

    /**
     * @return int
     */
    public function getNumberOfMessages();

    /**
     * @param $offset
     * @param $length
     * @return array Array of MessagesUser Entities or empty array
     */
    public function findMessages($offset, $length);

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