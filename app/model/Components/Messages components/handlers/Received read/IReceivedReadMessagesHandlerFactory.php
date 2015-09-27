<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\User;

interface IReceivedReadMessagesHandlerFactory
{
    /**
     * @param User $recipient
     * @return ReceivedReadMessagesHandler
     */
    public function create(User $recipient);
}