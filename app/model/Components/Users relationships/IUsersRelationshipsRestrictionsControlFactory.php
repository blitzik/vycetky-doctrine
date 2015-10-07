<?php

namespace App\Model\Components;

interface IUsersRelationshipsRestrictionsControlFactory
{
    /**
     * @param array $usersBlockedByMe
     * @param array $usersBlockingMe
     * @param array $suspendedUsers
     * @return UsersRelationshipsRestrictionsControl
     */
    public function create(
        array $usersBlockedByMe,
        array $usersBlockingMe,
        array $suspendedUsers
    );
}