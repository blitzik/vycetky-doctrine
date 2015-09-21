<?php

namespace App\Model\Components;

use App\Model\Query\InvitationsQuery;

interface IInvitationsManagementControlFactory
{
    /**
     * @param InvitationsQuery $invitationsQuery
     * @return InvitationsManagementControl
     */
    public function create(InvitationsQuery $invitationsQuery);
}