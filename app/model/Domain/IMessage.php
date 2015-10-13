<?php

namespace App\Model\Domain\Entities;

interface IMessage
{
    const SENT     = 'sent';
    const RECEIVED = 'received';
    const READ     = 'read';
    const UNREAD   = 'unread';

    /**
     * @return IMessage
     */
    public function getMessage();

    /**
     * @return bool
     */
    public function isSentMessage();

    /**
     * @return bool
     */
    public function isReceivedMessage();

    /**
     * @return User
     */
    public function getOwner();
}