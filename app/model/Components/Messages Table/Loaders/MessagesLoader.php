<?php

namespace MessagesLoaders;

use App\Model\Facades\MessagesFacade;
use Nette\Security\User;
use Nette\Object;

abstract class MessagesLoader extends Object
{
    /**
     * @var MessagesFacade
     */
    protected $messageFacade;

    /**
     * @var User
     */
    protected $user;


    public function __construct(
        MessagesFacade $messageFacade,
        User $user
    ) {
        $this->messageFacade = $messageFacade;
        $this->user = $user;
    }

}