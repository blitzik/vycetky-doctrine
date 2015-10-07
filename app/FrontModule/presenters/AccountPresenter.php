<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IUsersBlockingManagementControlFactory;
use App\Model\Components\IInvitationGenerationControlFactory;
use App\Model\Components\IInvitationsManagementControlFactory;
use App\Model\Facades\UsersFacade;
use App\Model\Query\InvitationsQuery;
use \Nette\Application\UI\Form;

class AccountPresenter extends SecurityPresenter
{
    //public $onDatabaseBackupSuccess = [];

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
     * @var \DatabaseBackup
     * @inject
     */
    //public $databaseBackup;

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;

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

        $this->usersFacade->saveUser($user);

        $this->flashMessage('Vaše jméno bylo úspěšně změněno.', 'success');
        $this->redirect('this');
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
     * todo - prijde do administrace pozdeji
     * @Actions detail
     */
    /*protected function createComponentBackupDatabaseForm()
    {
        $form = new Form();

        $form->addSubmit('backup', 'Provést zálohu')
                ->getControlPrototype()
                ->onClick = 'return confirm(\'Skutečně chcete provést zálohu databáze?\');';

        $form->onSuccess[] = [$this, 'processBackup'];

        $form->addProtection();

        return $form;
    }

    public function processBackup(Form $form)
    {
        if ($this->user->getIdentity()->role === 'administrator') {
            $file = WWW_DIR . '/app/backup/' . date('Y-m-d H-i-s') . '.sql';
            try {
                $this->databaseBackup->save($file);
                $this->flashMessage('Záloha databáze byla úspěšně provedena!', 'success');

            } catch (\Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }

            $validationObject = new SubscriberValidationObject();
            $this->onDatabaseBackupSuccess($file, $validationObject);
            if ($validationObject->isValid()) {
                $this->flashMessage('Soubor se zálohou byl úspěšně odeslán.', 'success');
            } else {
                $error = $validationObject->getFirstError();
                $this->flashMessage($error['message'], $error['type']);
            }

        } else {
            $this->flashMessage('Nemáte dostatečná oprávnění k provedení akce.', 'warning');
        }

        $this->redirect('this');
    }*/


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

}