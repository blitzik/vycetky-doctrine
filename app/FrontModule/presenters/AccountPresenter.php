<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IAccountPasswordControlFactory;
use App\Model\Components\IManualDatabaseBackupControlFactory;
use App\Model\Components\IUsersBlockingManagementControlFactory;
use App\Model\Components\IInvitationGenerationControlFactory;
use App\Model\Components\IInvitationsManagementControlFactory;
use App\Model\Facades\UsersFacade;
use App\Model\Query\InvitationsQuery;
use \Nette\Application\UI\Form;

class AccountPresenter extends SecurityPresenter
{
    /** @var array ['admin' => ... , 'system' => ...] */
    private $emails;

    /**
     * @var IUsersBlockingManagementControlFactory
     * @inject
     */
    public $usersBlockingManagementFactory;

    /**
     * @var IInvitationGenerationControlFactory
     * @inject
     */
    public $invitationGenerationFactory;

    /**
     * @var IInvitationsManagementControlFactory
     * @inject
     */
    public $invitationsManagementFactory;

    /**
     * @var IAccountPasswordControlFactory
     * @inject
     */
    public $accountPasswordFactory;

    /**
     * @var IManualDatabaseBackupControlFactory
     * @inject
     */
    public $manualDatabaseBackupFactory;

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;

    public function setEmails(array $emails)
    {
        $this->emails = $emails;
    }

    /*
     * --------------------
     * ------ DETAIL ------
     * --------------------
     */

    public function actionDetail()
    {
        $name = $this->user->getIdentity()->name;
        if (isset($name)) {
            $this['userForm']['name']->setDefaultValue($name);
        }
    }

    public function renderDetail()
    {
        $result = $this->usersFacade->getTotalWorkedStatistics($this->user->getIdentity());

        if (empty($result)) {
            $workedDays = 0;
            $totalWorkedHours = 0;
        } else {
            $workedDays = $result['worked_days'];

            $totalWorkedHours = $result['total_worked_hours'];
        }

        $this->template->totalWorkedDays = $workedDays;
        $this->template->totalWorkedHours = new \InvoiceTime((int)$totalWorkedHours);

    }

    /**
     * @Actions detail
     */
    protected function createComponentUserForm()
    {
        $form = new Form();

        $form->addText('name', 'Jméno', 13, 70);
        $form->addSubmit('savename', 'Uložit');

        $form->onSuccess[] = [$this, 'processSaveWholeName'];

        return $form;
    }

    public function processSaveWholeName(Form $form, $values)
    {
        $user = $this->user->getIdentity();
        $user->name = $values['name'];

        try {
            $this->usersFacade->saveUser($user);

            $this->flashMessage('Vaše jméno bylo úspěšně změněno.', 'success');
            $this->redirect('this');

        } catch (\Exception $e) {
            $this->flashMessage('Jméno nemohlo být změněno. Zkuste akci opakovat později.', 'error');
            return;
        }
    }


    /*
     * --------------------
     * ------ BACKUP ------
     * --------------------
     */

    public function actionDatabaseBackup()
    {
    }

    public function renderDatabaseBackup()
    {
    }


    /**
     * @Actions databaseBackup
     */
    protected function createComponentManualDatabaseBackup()
    {
        $comp = $this->manualDatabaseBackupFactory->create();
        $comp->onBeforeManualBackup[] = function () {
            if (!$this->authorizator->isAllowed($this->user->getIdentity(), 'database_backup')) {
                $this->flashMessage('Nemáte dostatečná oprávnění k provedení akce', 'warning');
                $this->redirect('this');
            }
        };

        return $comp;
    }


    /*
     * ---------------------------
     * ------ BLOCKED USERS ------
     * ---------------------------
     */

    public function actionBlockedUsers()
    {

    }

    public function renderBlockedUsers()
    {

    }

    /**
     * @Actions blockedUsers
     */
    protected function createComponentUsersList()
    {
        $comp = $this->usersBlockingManagementFactory
                     ->create($this->user->getIdentity());

        $comp->hideRelationshipsRestrictions();
        $comp->hideHintBox();

        return $comp;
    }


    /*
     * -------------------------
     * ------ INVITATIONS ------
     * -------------------------
     */

    public function actionInvitations()
    {

    }

    public function renderInvitations()
    {

    }

    /**
     * @Actions invitations
     */
    protected function createComponentInvitationsManager()
    {
        return $this->invitationsManagementFactory
                    ->create(
                        (new InvitationsQuery())
                        ->bySender($this->user->getIdentity())
                        ->onlyActive()
                    );
    }

    /*
     * -----------------------------
     * ------ SEND INVITATION ------
     * -----------------------------
     */


    public function actionSendInvitation()
    {

    }

    public function renderSendInvitation()
    {

    }

    /**
     * @Actions sendInvitation
     */
    protected function createComponentSendInvitationForm()
    {
        $comp = $this->invitationGenerationFactory->create();

        return $comp;
    }


    /*
     * -----------------------------
     * ------ PASSWORD CHANGE ------
     * -----------------------------
     */

    public function actionPassword()
    {
    }

    public function renderPassword()
    {
    }

    /**
     * @Actions password
     */
    protected function createComponentPassword()
    {
        return $this->accountPasswordFactory->create($this->user->getIdentity());
    }
}