<?php

namespace App\Model\Components;

use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\InvitationValidityException;
use Nette\InvalidStateException;
use Nette\Utils\Html;
use Nextras\Application\UI\SecuredLinksControlTrait;
use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use App\Model\Query\InvitationsQuery;
use Doctrine\ORM\AbstractQuery;
use Nette\Application\UI\Control;
use Nextras\Datagrid\Datagrid;

class InvitationsManagementControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var InvitationsFacade
     */
    private $invitationsFacade;

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

        $grid->addColumn('token', 'Registrační kód');
        $grid->addColumn('email', 'Příjemce');
        $grid->addColumn('validity', 'Platnost do');
        $grid->addColumn('lastSending', 'Lze odeslat znovu');

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

    /**
     * @secured
     */
    public function handleResendInvitation($id)
    {
        try {
            $invitation = $this->invitationsFacade
                               ->fetchInvitation(
                                   (new InvitationsQuery())
                                   ->byId($id)
                                   ->onlyActive()
                               );

            $this->invitationsFacade->sendInvitation($invitation);

            $this->flashMessage('Pozvánka byla úspěšně odeslána.', 'success');
            $this->redirect('this');

        } catch (InvitationValidityException $v) {
            $el = Html::el();
            $el->setText(
                'Pozvánka, jež se pokoušíte znovu odeslat, již
                 není aktivní. '
            );
            $link = Html::el('a')
                    ->href($this->presenter->link('Profile:sendInvitation'))
                    ->setHtml('Vytvořte novou pozvánku.');
            $el->add($link);

            $this->flashMessage($el, 'warning');

            $this->redirect('this');

        } catch (InvalidStateException $e) {
            $this->flashMessage('Pozvánku se nepodařilo odeslat.', 'warning');
            $this->flashMessage(
                'Pokud problém přetrvá,
                 Registrační kód můžete také předat sami danému uživateli,
                 který tento kód poté uplatní v registrační části přihlašovací
                 stránky.'
            );
            $this->redirect('this');
        }
    }

    /**
     * @secured
     */
    public function handleRemoveInvitation($id)
    {
        $this->invitationsFacade->removeInvitation($id);

        $this->flashMessage('Pozvánka byla úspěšně deaktivována.', 'success');
        $this->redirect('this');
    }
}