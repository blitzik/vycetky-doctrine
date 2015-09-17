<?php

namespace App\Model\Facades;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\User;
use Nette\Utils\Validators;
use Nette\Object;

abstract class BaseFacade extends Object
{
    /**
     * @var \Nette\Security\User
     */
    protected $user;

    public function __construct(\Nette\Security\User $user)
    {
        $this->user = $user;
    }

    /**
     * @param \App\Model\Entities\User|int $user
     * @return int
     */
    protected function getUserID($user)
    {
        $id = null;
        if ($user instanceof User and !$user->isDetached()) {
            $id = $user->userID;
        } else if (Validators::is($user, 'numericint')) {
            $id = $user;
        } else {
            throw new InvalidArgumentException(
                'Argument $user must be instance of '.User::class.'
                 or integer number.'
            );
        }

        return $id;
    }

    /**
     * @param \App\Model\Entities\User|int|null $user
     * @return int
     */
    protected function getIdOfSignedInUserOnNull($user = null)
    {
        if ($user === null) {
            return $this->user->id;
        } else {
            return $this->getUserID($user);
        }
    }
}