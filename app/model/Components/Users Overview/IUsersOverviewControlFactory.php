<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;

interface IUsersOverviewControlFactory
{
    /**
     * @param User $user
     * @return UsersOverviewControl
     */
    public function create(User $user);
}