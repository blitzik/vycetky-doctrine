<?php

namespace App\UserModule\Presenters;

use App\Model\Facades\InvitationsFacade;
use App\Model\Facades\UsersFacade;
use Exceptions\Runtime\InvitationValidityException;
use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Utils\Validators;

class AccountPresenter extends Presenter
{
    /**
     * @var InvitationsFacade
     * @inject
     */
    public $invitationsFacade;

    /**
    * @var UsersFacade
    * @inject
    */
    public $usersFacade;

    /**
     * @var Invitation
     */
    private $invitation;

    /**
     * @var EntityManager
     * @inject
     */
    public $em;

    /*
     * ------------------------
     * ----- REGISTRATION -----
     * ------------------------
     */

    public function actionRegistration($email, $token)
    {
        if (!Validators::is($email, 'email')) {
            $this->flashMessage('E-mailová adresa nemá platný formát.', 'warning');
            $this->redirect('Account:default');
        }

        try {
            $this->invitation = $this->invitationsFacade->checkInvitation($email, $token);

        } catch (\Exceptions\Runtime\InvitationValidityException $t) {
            $this->flashMessage('Registrovat se může pouze uživatel s platnou pozvánkou.', 'warning');
            $this->redirect('Account:login');
        }

        $this['registrationForm']['email']->setDefaultValue($this->invitation->email);
    }

    public function renderRegistration($token)
    {
    }

    protected function createComponentRegistrationForm()
    {
        $form = new Form();

        $form->addText('username', 'Uživatelské jméno:', null, 25)
                ->setRequired('Vyplňte své uživatelské jméno prosím.')
                ->setAttribute('placeholder', 'Vyplňte jméno');

        $form->addpassword('password', 'Uživatelské heslo:')
                ->setRequired('Vyplňte své heslo prosím.')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 5)
                ->setAttribute('placeholder', 'Vyplňte heslo')
                ->setHtmlId('password-input');

        $form->addPassword('pass2', 'Kontrola hesla:')
                ->setRequired('Vyplňte kontrolu vašeho hesla prosím.')
                ->addRule(Form::EQUAL, 'Zadaná hesla se musí shodovat.', $form['password'])
                ->setOmitted()
                ->setAttribute('placeholder', 'Zadejte heslo znovu')
                ->setHtmlId('password-control-input');

        $form->addText('email', 'E-mail:')
                ->getControlPrototype()->readonly = 'readonly';
                /*->setRequired('Vyplňte váš email prosím.')
                ->addRule(Form::EMAIL, 'Zadejte prosim platný formát E-mailové adresy.');*/

        $form->addSubmit('reg', 'Vytořit účet')
             ->setOmitted()
             ->setHtmlId('password-save-button');

        $form->onSuccess[] = callback($this, 'processUserRegistration');

        return $form;

    }

    public function processUserRegistration(Form $form)
    {
        $values = $form->getValues();
        $forbiddenNames = array_flip(['systém', 'system', 'admin', 'administrator',
                                      'administrátor']);

        if (array_key_exists(strtolower($values['username']), $forbiddenNames)) {
            $form->addError('Vámi zadané jméno nelze použít. Vyberte si prosím jiné.');
            return;
        }

        $values['ip']   = $this->getHttpRequest()->getRemoteAddress();
        $values['role'] = 'employee';

        $user = new User(
            $values['username'],
            $values['password'],
            $values['email'],
            $values['ip'],
            $this->invitation->getSender(),
            $values['role']
        );

        try {
            $this->usersFacade->registerNewUser($user, $this->invitation);

            $this->flashMessage('Váš účet byl vytvořen. Nyní se můžete přihlásit.', 'success');
            $this->redirect('Login:default');

        } catch (InvitationValidityException $iu) {
            $this->flashMessage('Registrovat se může pouze uživatel s platnou pozvánkou.', 'warning');
            $this->redirect('Login:default');

        } catch (\Exceptions\Runtime\DuplicateUsernameException $du) {
            $form->addError('Vámi zvolené jméno vužívá již někdo jiný. Vyberte si prosím jiné jméno.');

        } catch (\Exceptions\Runtime\DuplicateEmailException $de) {
            $form->addError("Zadejte prosím jiný E-mail.");

        } catch (\DibiException $d) {
            $form->addError('Registraci nelze dokončit. Zkuste to prosím později.');
        }

    }

}