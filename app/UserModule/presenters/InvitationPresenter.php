<?php

namespace App\UserModule\Presenters;

use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use Exceptions\Runtime\InvitationValidityException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

class InvitationPresenter extends Presenter
{
    /**
     * @var InvitationsFacade
     * @inject
     */
    public $invitationsFacade;

    public function actionApplication()
    {

    }

    public function renderApplication()
    {

    }

    /**
     * @Actions application
     */
    protected function createComponentInvitationApplication()
    {
        $form = new Form();

        $form->addText('email', 'E-mail svázaný s pozvánkou')
                ->setRequired('Vyplňte prosím pole pro E-mail.')
                ->addRule(Form::EMAIL, 'Zadejte E-mailovou adresu ve správném formátu');

        $form->addText('token', 'Registrační kód pozvánky', null, 15)
                ->setRequired('Zadejte registrační kód');
        
        $form->addSubmit('send', 'Zkontrolovat pozvánku')
                ->setHtmlId('invitation-process-button');
        
        $form->onSuccess[] = [$this, 'processInvitation'];

        return $form;
    }

    public function processInvitation(Form $form, $values)
    {
        $errMsg = 'Neplatná pozvánka';
        if (mb_strlen($values['token']) !== Invitation::TOKEN_LENGTH) {
            $form->addError($errMsg);
            return;
        }

        try {
            $this->invitationsFacade
                 ->checkInvitation($values['email'], $values['token']);

            $this->redirect(
                'Account:registration',
                ['email' => $values['email'], 'token' => $values['token']]
            );

        } catch (InvitationValidityException $e) {
            $form->addError($errMsg);
        }
    }
}