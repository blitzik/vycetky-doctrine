<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Message;

interface IMessageDetailControlFactory
{
    /**
     * @param Message $message
     * @return MessageDetailControl
     */
    public function create(Message $message);
}