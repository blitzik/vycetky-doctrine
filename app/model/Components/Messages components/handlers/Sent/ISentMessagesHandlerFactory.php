<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\User;

interface ISentMessagesHandlerFactory
{
    /**
     * @param User $messagesAuthor
     * @return SentMessagesHandler
     */
    public function create(User $messagesAuthor);
}