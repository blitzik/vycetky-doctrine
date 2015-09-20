<?php

namespace App\UserModule\Presenters;

use App\Model\Facades\UsersFacade;
use Exceptions\Runtime\InvitationValidityException;
use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Form;
use Nette\Utils\Validators;

class AccountPresenter extends BasePresenter
{
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
     * --------------------
     * ----- Default -----
     * --------------------
     */

    public function actionDefault()
    {
        //$invitation = new Invitation('septhaiah@gmail.com', new \DateTime('2015-09-30'));
        //$this->em->persist($invitation)->flush();
    }

    public function renderDefault()
    {
    }

    protected function createComponentLoginForm()
    {
        $form = new Form();

        $form->addText('email', 'E-mailová adresa uživatele:', 25, 70)
                ->setRequired('Zadejte prosím svůj E-mail.')
                ->addRule(Form::EMAIL, 'Zadejte prosím E-mailovou adresu ve správném formátu.');

        $form->addPassword('pass', 'Heslo:', 25)
                ->setRequired('Zadejte prosím své heslo.')
                ->addRule(Form::FILLED, 'Zadejte vaše heslo prosím.');

        $form->addCheckbox('keepLogin', 'Zůstat přihlášen')
                ->setDefaultValue(true);

        $form->addSubmit('login', 'Přihlásit')
                ->setOmitted();

        $form->onSuccess[] = callback($this, 'processLoginForm');

        return $form;

    }

    public function processLoginForm(Form $form)
    {
        $values = $form->getValues();

        try{
           $this->user->login($values['email'], $values['pass']);

           if ($values['keepLogin']) {
               $this->user->setExpiration('+30 days', false);
           } else {
               $this->user->setExpiration('+1 hour', true);
           }

           $currentDate = new \DateTime('now');
           $this->redirect(
               ':Front:Listing:overview',
               ['year' => $currentDate->format('Y'),
                'month' => $currentDate->format('n')]
               );

        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
            return;
        }
    }

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
            $this->invitation = $this->usersFacade->checkInvitation($email, $token);

        } catch (\Exceptions\Runtime\InvitationValidityException $t) {
            $this->flashMessage('Registrovat se může pouze uživatel s platnou pozvánkou.', 'warning');
            $this->redirect('Account:default');
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

        $form->addSubmit('reg', 'Zaregistrovat uživatele')
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
            $values['role']
        );

        try {
            $this->usersFacade->registerNewUser($user, $this->invitation);

            $this->flashMessage('Váš účet byl vytvořen. Nyní se můžete přihlásit.', 'success');
            $this->redirect('Account:default');

        } catch (InvitationValidityException $iu) {
            $this->flashMessage('Registrovat se může pouze uživatel s platnou pozvánkou.', 'warning');
            $this->redirect('Account:default');

        } catch (\Exceptions\Runtime\DuplicateUsernameException $du) {
            $form->addError('Vámi zvolené jméno vužívá již někdo jiný. Vyberte si prosím jiné jméno.');

        } catch (\Exceptions\Runtime\DuplicateEmailException $de) {
            $form->addError("Zadejte prosím jiný E-mail.");

        } catch (\DibiException $d) {
            $form->addError('Registraci nelze dokončit. Zkuste to prosím později.');
        }

    }

}