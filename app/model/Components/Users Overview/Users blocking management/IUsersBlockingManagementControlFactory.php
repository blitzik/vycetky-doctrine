<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;

interface IUsersBlockingManagementControlFactory
{
    /**
     * @param User $user
     * @return UsersBlockingManagementControl
     */
    public function create(User $user);
}