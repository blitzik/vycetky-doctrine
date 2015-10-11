<?php

namespace App\UserModule\Presenters;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use App\Model\Factories\PasswordChangeFormFactory;
use App\Model\Notifications\EmailNotifier;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\Form;
use Tracy\Debugger;
use Nette;

class PasswordPresenter extends Nette\Application\UI\Presenter
{
    /**
     * @var PasswordChangeFormFactory
     * @inject
     */
    public $passwordChangeFactory;

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
    private $userEntity;

    /**
     * @var string
     */
    private $systemEmail;

    public function setSystemEmail($systemEmail)
    {
        $this->systemEmail = $systemEmail;
    }

    /*
     * ----------------------------
     * ------ PASSWORD RESET ------
     * ----------------------------
     */

    public function actionReset()
    {
    }

    public function renderReset()
    {
    }

    /**
     * @Actions reset
     */
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

        $this->redirect('Login:default');
    }

    /*
     * -----------------------------
     * ------ PASSWORD CHANGE ------
     * -----------------------------
     */

    public function actionChange($email, $token)
    {
        $this->userEntity = $this->usersFacade->getUserByEmail($email);

        if ($this->userEntity === null or
            $this->userEntity->token === null or
            $this->userEntity->token !== $token)
        {
            $this->flashMessage('Nelze změnit heslo účtu spojeného s E-mailem ' .$email, 'warning');
            $this->redirect('Password:reset');
        }

        $currentTime = new \DateTime;
        if ($currentTime > $this->userEntity->tokenValidity) {
            $this->flashMessage('Čas na změnu hesla vypršel. Pro obnovu hesla využijte formuláře níže.', 'warning');
            $this->redirect('Password:reset');
        }

        $this['passwordChangeForm']['username']->setDefaultValue($this->userEntity->username);
        $this['passwordChangeForm']['email']->setDefaultValue($this->userEntity->email);

    }

    public function renderChange($email, $token)
    {
    }

    /**
     * @Actions change
     */
    protected function createComponentPasswordChangeForm()
    {
        $form = $this->passwordChangeFactory->create($this->userEntity);
        unset($form['currentPassword']);

        $form->addText('username', 'Uživatel:')
                ->setRequired()
                ->getControlPrototype()->readonly = 'readonly';

        $form->addText('email', 'E-mail:')
                ->setRequired()
                ->getControlPrototype()->readonly = 'readonly';

        $this->passwordChangeFactory
             ->onBeforeChange[] = [$this, 'onBeforeChange'];

        $this->passwordChangeFactory
             ->onSuccess[] = [$this, 'onSuccess'];

        $this->passwordChangeFactory
             ->onError = [$this, 'onError'];

        return $form;
    }

    public function onBeforeChange(Form $form, User $user)
    {
        $values = $form->getValues();

        if ($user->email != $values['email']) {
            $this->flashMessage(
                'Vámi zadaný E-mail nesouhlasí s E-mailem, na který byl
                 zaslán požadavek o změnu hesla!',
                'warning'
            );
            $this->redirect('this');
        }

        $user->resetToken();
    }

    public function onSuccess(Form $form, User $user)
    {
        $this->flashMessage(
            'Heslo bylo změněno. Nyní se můžete přihlásit.',
            'success'
        );
        $this->redirect('Login:default');
    }

    public function onError(Form $form, User $user)
    {
        $this->flashMessage(
            'Při pokusu o změnu hesla došlo k chybě. Na nápravě se
             pracuje. Zkuste to prosím později.',
            'warning'
        );
        $this->redirect('this');
    }

}