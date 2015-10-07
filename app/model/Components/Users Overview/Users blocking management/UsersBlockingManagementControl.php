<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use Components\IPaginatorFactory;

class UsersBlockingManagementControl extends UsersOverviewControl
{
    public function __construct(
        User $userEntity,
        UsersFacade $usersFacade,
        IPaginatorFactory $paginatorFactory,
        IUserBlockingControlFactory $userBlockingControlFactory,
        IUsersRelationshipsRestrictionsControlFactory $relationshipsRestrictionsControlFactory
    ) {
        parent::__construct(
            $userEntity,
            $usersFacade,
            $paginatorFactory,
            $userBlockingControlFactory,
            $relationshipsRestrictionsControlFactory
        );

        // slightly modified query
        $this->usersQuery->findUsersBlockedBy($userEntity);
    }

    public function onUnblockUser(UserBlockingControl $control, User $user = null)
    {
        // overridden method, now we want to refresh entire table
        $this->users = [];
        $this->alreadyBlockedUsers = [];

        $this->redrawControl();
    }
}