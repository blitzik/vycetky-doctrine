<?php

namespace App\UserModule\Presenters;

use App\Model\Facades\UsersFacade;
use App\Model\Notifications\EmailNotifier;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\Form;
use Tracy\Debugger;
use Nette;

class PasswordPresenter extends Nette\Application\UI\Presenter
{
    /**
     * @var EmailNotifier
     * @inject
     */
    public $emailNotifier;

    /**
     * @var UsersFacade
     * @inject
     */
    public $usersFacade;

    /**
     * @var \App\Model\Domain\Entities\User
     */
    private $user;

    /**
     * @var string
     */
    private $systemEmail;

    public function setSystemEmail($systemEmail)
    {
        $this->systemEmail = $systemEmail;
    }

    protected function createComponentPasswordReset()
    {
        $form = new Form();

        $form->addText('email', 'E-mail:')
                ->setRequired('Zadejte Vaši E-mailovou adresu.')
                ->addRule(Form::EMAIL, 'Vložte E-mailovou adresu ve správném tvaru.');

        $form->addSubmit('reset', 'Resetovat heslo');

        $form->onSuccess[] = [$this, 'processPasswordReset'];

        return $form;
    }

    public function processPasswordReset(Form $form)
    {
        $values = $form->getValues();

        try {
            $user = $this->usersFacade
                         ->createPasswordRestoringToken($values['email']);

        } catch (\Exceptions\Runtime\UserNotFoundException $u) {
            $form->addError('Nelze obnovit heslo na zadaném E-mailu.');
            return;
        }

        try {
            $this->emailNotifier->send(
                'Výčetkový systém <' .$this->systemEmail. '>',
                $user->email,
                function (ITemplate $template, $email, $token) {
                    $template->setFile(__DIR__ . '/../../model/Notifications/templates/resetPassword.latte');
                    $template->email = $email;
                    $template->token = $token;

                },
                [$user->email, $user->token]
            );

            $this->flashMessage(
                'Na Váš registrační E-Mail byly odeslány instrukce ke změně hesla.',
                'success'
            );

        } catch (Nette\InvalidStateException $e) {
            Debugger::log($e);
            $this->flashMessage(
                'Při zpracování došlo k chybě. Zkuste prosím akci opakovat později.',
                'error'
            );
        }

        $this->redirect('Account:login');
    }


    public function actionChange($email, $token)
    {
        $this->user = $this->usersFacade->getUserByEmail($email);

        if ($this->user === null or
            $this->user->token === null or
            $this->user->token !== $token)
        {
            $this->flashMessage('Nelze změnit heslo účtu spojeného s E-mailem ' .$email, 'warning');
            $this->redirect('Password:reset');
        }

        $currentTime = new \DateTime;
        if ($currentTime > $this->user->tokenValidity) {
            $this->flashMessage('Čas na změnu hesla vypršel. Pro obnovu hesla využijte formuláře níže.', 'warning');
            $this->redirect('Password:reset');
        }

        $this['passwordChangeForm']['username']->setDefaultValue($this->user->username);
        $this['passwordChangeForm']['email']->setDefaultValue($this->user->email);

    }

    public function renderChange($email, $token)
    {
    }

    protected function createComponentPasswordChangeForm()
    {
        $form = new Form();

        $form->addText('username', 'Uživatel:')
                ->setRequired()
                ->getControlPrototype()->readonly = 'readonly';

        $form->addText('email', 'E-mail:')
                ->setRequired()
                ->getControlPrototype()->readonly = 'readonly';

        $form->addPassword('password', 'Nové heslo:')
                ->setRequired('Vyplňte své heslo.')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 5)
                ->setAttribute('placeholder', 'Zadejte nové heslo')
                ->setHtmlId('password-input');;

        $form->addPassword('password2', 'Kontrola hesla:')
                ->setRequired('Vyplňte kontrolu hesla.')
                ->addRule(Form::EQUAL, 'Zadaná hesla se musí shodovat.', $form['password'])
                ->setAttribute('placeholder', 'Znovu zadejte své heslo')
                ->setHtmlId('password-control-input');

        $form->addSubmit('save', 'Změnit heslo')
             ->setHtmlId('password-save-button');

        $form->onSuccess[] = callback($this, 'processChangePassword');

        return $form;
    }

    public function processChangePassword(Form $form)
    {
        $values = $form->getValues();

        if ($this->user->email != $values['email']) {
            $this->flashMessage('Vámi zadaný E-mail nesouhlasí s E-mailem, na který byl zaslán požadavek o změnu hesla!', 'warning');
            $this->redirect('this');
        }

        try {
            $this->user->resetToken();
            $this->user->password = $values['password'];

            $this->usersFacade->saveUser($this->user);

        } catch (\Exception $e) {
            $this->flashMessage('Při pokusu o změnu hesla došlo k chybě. Na nápravě se pracuje. Zkuste to prosím později.', 'warning');
            $this->redirect('this');
        }

        $this->flashMessage('Heslo bylo změněno. Nyní se můžete přihlásit.', 'success');
        $this->redirect('Account:login');
    }

}