<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;

interface IAccountPasswordControlFactory
{
    /**
     * @param USer $user
     * @return AccountPasswordControl
     */
    public function create(User $user);
}