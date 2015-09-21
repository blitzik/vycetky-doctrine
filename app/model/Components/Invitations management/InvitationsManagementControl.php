<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use App\Model\Domain\Entities\User;
use App\Model\Query\InvitationsQuery;
use Doctrine\ORM\AbstractQuery;
use Nette\Application\UI\Control;
use Nextras\Datagrid\Datagrid;

class InvitationsManagementControl extends Control
{
    /**
     * @var InvitationsFacade
     */
    private $invitationsFacade;

    /**
     * @var User
     */
    private $user;

    /**
     * @var InvitationsQuery
     */
    private $invitationsQuery;

    /**
     * @var Invitation[]
     */
    private $invitations;

    public function __construct(
        InvitationsQuery $invitationsQuery,
        InvitationsFacade $invitationsFacade
    ) {
        $this->invitationsQuery = $invitationsQuery;
        $this->invitationsFacade = $invitationsFacade;
    }

    protected function createComponentInvitationsDatagrid()
    {
        $grid = new Datagrid();

        $grid->addColumn('createdAt', 'Vytvořena');
        $grid->addColumn('token', 'Číslo pozvánky');
        $grid->addColumn('email', 'Příjemce');
        $grid->addColumn('validity', 'Platnost do');

        $grid->setRowPrimaryKey('token');

        $grid->setDataSourceCallback(function ($filter, $order) {
            return $this->invitations;
        });

        $grid->addCellsTemplate(__DIR__ . '/templates/grid/grid.latte');

        return $grid;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $this->invitations = $this->invitationsFacade
                                  ->fetchInvitations($this->invitationsQuery)
                                  ->toArray(AbstractQuery::HYDRATE_SIMPLEOBJECT);

        $template->hasInvitations = !empty($this->invitations);

        $template->render();
    }
}