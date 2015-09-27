<?php

namespace App\Model\MessagesHandlers;

use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use Nette\Object;

abstract class MessagesHandler extends Object
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var MessagesFacade
     */
    protected $messagesFacade;

    public function __construct(
        User $user,
        MessagesFacade $messagesFacade
    ) {
        $this->messagesFacade = $messagesFacade;
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

}