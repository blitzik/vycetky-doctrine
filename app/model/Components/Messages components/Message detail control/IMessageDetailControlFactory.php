<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\IMessage;

interface IMessageDetailControlFactory
{
    /**
     * @param IMessage $message
     * @return MessageDetailControl
     */
    public function create(IMessage $message);
}