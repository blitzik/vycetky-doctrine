<?php

namespace App\Model\Components;

use Components\IPaginatorFactory;
use Exceptions\Runtime\InvitationValidityException;
use Nette\InvalidStateException;
use Nette\Utils\Html;
use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use App\Model\Query\InvitationsQuery;

class InvitationsManagementControl extends BaseComponent
{
    /** @var IPaginatorFactory  */
    private $paginatorFactory;

    /** @var InvitationsFacade  */
    private $invitationsFacade;

    /** @var InvitationsQuery  */
    private $invitationsQuery;

    /** @var Invitation[] */
    private $invitations;


    public function __construct(
        InvitationsQuery $invitationsQuery,
        InvitationsFacade $invitationsFacade,
        IPaginatorFactory $paginatorFactory
    ) {
        $this->invitationsQuery = $invitationsQuery;
        $this->invitationsFacade = $invitationsFacade;
        $this->paginatorFactory = $paginatorFactory;
    }

    protected function createComponentPaginator()
    {
        $comp = $this->paginatorFactory->create();
        $comp->onPaginate[] = function () {
            $this->redrawControl();
        };

        return $comp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        $resultSet = $this->invitationsFacade
                          ->fetchInvitations($this->invitationsQuery);

        $paginator = $this['paginator']->getPaginator();
        $resultSet->applyPaginator($paginator, 15);

        $this->invitations = $resultSet->toArray();
        $template->invitations = $this->invitations;

        $template->hasInvitations = !empty($this->invitations);

        $template->render();
    }

    /**
     * @secured
     */
    public function handleResendInvitation($id)
    {
        try {
            $this->invitationsFacade->sendInvitation($id);
            $this->flashMessage('Pozvánka byla úspěšně odeslána.', 'success');

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

        } catch (InvalidStateException $e) {
            $this->flashMessage('Pozvánku se nepodařilo odeslat.', 'warning');
            $this->flashMessage(
                'Pokud problém přetrvá,
                 Registrační kód můžete také předat sami danému uživateli,
                 který tento kód poté uplatní v registrační části přihlašovací
                 stránky.'
            );
        }

        $this->refreshTable();
    }

    /**
     * @secured
     */
    public function handleRemoveInvitation($id)
    {
        $this->invitationsFacade->removeInvitation($id);
        $this->flashMessage('Pozvánka byla úspěšně deaktivována.', 'success');

        $this->refreshTable();
    }

    private function refreshTable()
    {
        if ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }
}