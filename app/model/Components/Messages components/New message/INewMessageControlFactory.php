<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;

interface INewMessageControlFactory
{
    /**
     * @param User $user
     * @return NewMessageControl
     */
    public function create(User $user);
}