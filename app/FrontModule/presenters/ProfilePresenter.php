<?php

namespace App\FrontModule\Presenters;

use App\Model\Subscribers\Validation\SubscriberValidationObject;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\UserAlreadyExistsException;
use App\Model\Facades\UsersFacade;
use \Nette\Application\UI\Form;

class ProfilePresenter extends SecurityPresenter
{
    /**
     * @var array
     */
    public $onInvitationCreation = [];
    public $onDatabaseBackupSuccess = [];


    /**
     * @var \DatabaseBackup
     * @inject
     */
    public $databaseBackup;

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
    protected function createComponentBackupDatabaseForm()
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
    }

    protected function createComponentSendKeyForm()
    {
        $form = new Form();

        $form->addText('email', 'Odeslat pozvánku na adresu:', 22)
                ->setRequired('Zadejte prosím E-mail, na který se má pozvánka odeslat.')
                ->addRule(Form::EMAIL, 'Zadejte platnou E-Mailovou adresu.');

        $form->addSubmit('send', 'Odeslat pozvánku');

        $form->onSuccess[] = [$this, 'processCreateInvitation'];

        return $form;
    }

    public function processCreateInvitation(Form $form)
    {
        $value = $form->getValues();

        try {
            $invitation = $this->usersFacade->createInvitation($value['email']);
        } catch (UserAlreadyExistsException $uae) {
            $this->flashMessage(
                'Pozvánku nelze odeslat. Uživatel s E-Mailem ' . $value['email'] . ' je již zaregistrován.',
                'warning'
            );
            $this->redirect('this');

        } catch (InvitationAlreadyExistsException $iae) {
            $this->flashMessage(
                'Pozvánka již byla odeslána uživateli s E-mailem ' .$value['email'],
                'warning'
            );
            $this->redirect('this');
        }

        $validationObject = new SubscriberValidationObject();
        $this->onInvitationCreation($invitation, $validationObject);
        if ($validationObject->isValid()) {
            $this->flashMessage(
                'Registrační pozvánka byla odeslána',
                'success'
            );
        } else {
            $error = $validationObject->getFirstError();
            $this->flashMessage($error['message'], $error['type']);
        }

        $this->redirect('this');
    }

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
        $user = $this->usersFacade->getUserByID($this->user->id);
        $user->name = $values['name'];

        $this->usersFacade->saveUser($user);
        $this->user->getIdentity()->name = $values['name'];

        $this->flashMessage('Vaše jméno bylo úspěšně změněno.', 'success');
        $this->redirect('this');
    }
}