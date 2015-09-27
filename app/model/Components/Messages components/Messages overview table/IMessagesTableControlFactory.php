<?php

namespace App\Model\Components;


use App\Model\MessagesHandlers\IMessagesHandler;

interface IMessagesTableControlFactory
{
    /**
     * @param IMessagesHandler $handler
     * @return MessagesTableControl
     */
    public function create(IMessagesHandler $handler);
}