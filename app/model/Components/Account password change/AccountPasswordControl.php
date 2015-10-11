<?php

namespace App\Model\Components;

use App\Model\Factories\PasswordChangeFormFactory;
use App\Model\Domain\Entities\User;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;

class AccountPasswordControl extends Control
{
    /** @var PasswordChangeFormFactory  */
    private $passwordChangeFormFactory;

    /** @var User  */
    private $user;

    public function __construct(
        User $user,
        PasswordChangeFormFactory $passwordChangeFormFactory
    ) {
        $this->user = $user;
        $this->passwordChangeFormFactory = $passwordChangeFormFactory;
    }

    protected function createComponentPasswordForm()
    {
        $form = $this->passwordChangeFormFactory->create($this->user);

        $this->passwordChangeFormFactory
             ->onBeforeChange[] = [$this, 'onBeforeChange'];

        $this->passwordChangeFormFactory
             ->onSuccess[] = [$this, 'onSuccess'];

        $this->passwordChangeFormFactory
             ->onError[] = [$this, 'onError'];

        return $form;
    }

    public function onBeforeChange(Form $form, User $user)
    {
        $values = $form->getValues();
        if (!Passwords::verify($values['currentPassword'], $user->password)) {
            $this->flashMessage(
                'Heslo nelze změnit, protože nesouhlasí
                 Vaše aktuální heslo.',
                'warning'
            );
            $this->redirect('this');
        }
    }

    public function onSuccess(Form $form, User $user)
    {
        $this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
        $this->redirect('this');
    }

    public function onError(Form $form, User $user)
    {
        $this->flashMessage('Při pokusu o změnu hesla došlo k chybě.', 'error');
        $this->redirect('this');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->render();
    }
}