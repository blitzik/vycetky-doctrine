<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use Components\IPaginatorFactory;

class UsersBlockingManagementControl extends UsersOverviewControl
{
    public function __construct(
        User $user,
        UsersFacade $usersFacade,
        IUserBlockingControlFactory $userBlockingControlFactory,
        IPaginatorFactory $paginatorFactory
    ) {
        parent::__construct(
            $user,
            $usersFacade,
            $userBlockingControlFactory,
            $paginatorFactory
        );

        // slightly modified query
        $this->usersQuery->findUsersBlockedBy($user);
    }

    public function onUnblockUser(UserBlockingControl $control, User $user = null)
    {
        // overridden method, now we want to refresh entire table
        $this->users = [];
        $this->alreadyBlockedUsers = [];

        $this->redrawControl();
    }
}