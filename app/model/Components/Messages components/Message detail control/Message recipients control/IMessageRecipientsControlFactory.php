<?php

namespace App\Model\Components;


interface IMessageRecipientsControlFactory
{
    /**
     * @param array $recipients
     * @return MessageRecipientsControl
     */
    public function create(array $recipients);
}