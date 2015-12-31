<?php

namespace App\Model\Factories;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use Nette\Application\UI\Form;
use Nette\Object;

class PasswordChangeFormFactory extends Object
{
    /** @var  array */
    public $onBeforeChange = [];
    public $onSuccess = [];
    public $onError = [];

    /** @var UsersFacade  */
    private $usersFacade;

    /** @var  User */
    private $user;


    public function __construct(UsersFacade $usersFacade)
    {
        $this->usersFacade = $usersFacade;
    }


    /**
     * @param User $user
     * @return Form
     */
    public function create(User $user)
    {
        $this->user = $user;

        $form = new Form;

        $form->addPassword('currentPassword', 'Aktuální heslo')
                ->setRequired('Zadejte své aktuální heslo');

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

        $form->addProtection();

        $form->onSuccess[] = [$this, 'processChangePassword'];

        return $form;
    }


    public function processChangePassword(Form $form, $values)
    {
        $this->onBeforeChange($form, $this->user);

        try {
            $this->user->password = $values['password'];

            $this->usersFacade->saveUser($this->user);

        } catch (\Exception $e) {
            $this->onError($form, $this->user);
        }

        $this->onSuccess($form, $this->user);
    }

}