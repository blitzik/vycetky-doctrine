<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Message;

interface IMessageRecipientsControlFactory
{
    /**
     * @param Message $message
     * @return MessageRecipientsControl
     */
    public function create(Message $message);
}