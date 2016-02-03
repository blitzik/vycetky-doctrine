<?php

namespace App\UserModule\Presenters;

use Exceptions\Runtime\InaccessibleAccountException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

class LoginPresenter extends Presenter
{
    public function actionDefault()
    {

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

        $form->onSuccess[] = [$this, 'processLoginForm'];

        return $form;

    }

    public function processLoginForm(Form $form)
    {
        $values = $form->getValues();

        try{
            $this->user->login($values['email'], $values['pass']);

            if ($values['keepLogin']) {
                $this->user->setExpiration('+14 days', false);
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
        } catch (InaccessibleAccountException $a) {
            $this->flashMessage(
                'Váš účet byl uzavřen.
                 Pro více informací kontaktujte správce aplikace na adrese:
                 vycetkovy-system@alestichava.cz', 'warning'
            );
            $this->redirect('this');
        }
    }

}