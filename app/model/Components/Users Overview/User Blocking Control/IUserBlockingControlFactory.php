<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;

interface IUserBlockingControlFactory
{
    /**
     * @param int $userBeingBlockedId
     * @param User $user
     * @return UserBlockingControl
     */
    public function create($userBeingBlockedId, User $user);
}