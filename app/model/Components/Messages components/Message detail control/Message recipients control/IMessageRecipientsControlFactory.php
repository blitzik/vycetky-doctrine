<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\SentMessage;

interface IMessageRecipientsControlFactory
{
    /**
     * @param SentMessage $message
     * @return MessageRecipientsControl
     */
    public function create(SentMessage $message);
}