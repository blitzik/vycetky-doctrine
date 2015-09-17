<?php

namespace App\FrontModule\Presenters;

use Exceptions\Runtime\InvitationAlreadyExistsException;
use App\Model\Notifications\EmailNotifier;
use Exceptions\Runtime\UserAlreadyExistsException;
use Nette\Application\UI\ITemplate;
use App\Model\Entities\Invitation;
use App\Model\Facades\UserManager;
use Nette\InvalidStateException;
use \Nette\Application\UI\Form;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Tracy\Debugger;

class ProfilePresenter extends SecurityPresenter
{
    /**
     * @var \DatabaseBackup
     * @inject
     */
    public $databaseBackup;

    /**
     * @var EmailNotifier
     * @inject
     */
    public $emailNotifier;

    /**
     * @var UserManager
     * @inject
     */
    public $userManager;

    /**
     * @var IMailer
     * @inject
     */
    public $mailer;

    /**
     * @var array ['admin' => ... , 'system' => ...]
     */
    private $emails;

    public function setEmails(array $emails)
    {
        $this->emails = $emails;
    }

    /*
     * --------------------
     * ----- OVERVIEW -----
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
        $result = $this->userManager->getTotalWorkedStatistics($this->user->getIdentity());

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

            $mail = new Message();
            $mail->setFrom('Výčetkový systém <' .$this->emails['system']. '>')
                 ->addTo($this->emails['admin'])
                 ->setSubject('Záloha databáze')
                 ->addAttachment($file);

            try {
                $this->mailer->send($mail);
                $this->flashMessage('Soubor se zálohou byl úspěšně odeslán.', 'success');

            } catch (InvalidStateException $is) {
                $this->flashMessage('Soubor se zálohou se nepodařilo odeslat.', 'warning');
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

        $form->onSuccess[] = [$this, 'processSendKey'];

        return $form;
    }

    public function processSendKey(Form $form)
    {
        $value = $form->getValues();

        try {
            $invitation = $this->userManager->createInvitation($value['email']);
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

        try {
            $this->emailNotifier->send(
                'Výčetkový systém <' . $this->emails['system']. '>',
                $invitation->email,
                function (ITemplate $template, Invitation $invitation, $senderName) {
                    $template->setFile(__DIR__ . '/../../model/Notifications/templates/invitation.latte');
                    $template->invitation = $invitation;
                    $template->username = $senderName;

                },
                [$invitation, $this->getUser()->getIdentity()->username]
            );

            $this->flashMessage(
                'Registrační pozvánka byla odeslána',
                'success'
            );

        } catch (InvalidStateException $e) {
            $this->userManager->removeInvitation($invitation);
            Debugger::log($e, Debugger::ERROR);
            $this->flashMessage(
                'Registrační pozvánku nebylo možné odeslat. Zkuste to prosím později.',
                'error'
            );
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
        $user = $this->userManager->getUserByID($this->user->id);
        $user->name = $values['name'];

        $this->userManager->saveUser($user);
        $this->user->getIdentity()->name = $values['name'];

        $this->flashMessage('Vaše jméno bylo úspěšně změněno.', 'success');
        $this->redirect('this');
    }
}