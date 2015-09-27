<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\User;

interface IReceivedUnreadMessagesHandlerFactory
{
    /**
     * @param User $recipient
     * @return ReceivedUnreadMessagesHandler
     */
    public function create(User $recipient);
}