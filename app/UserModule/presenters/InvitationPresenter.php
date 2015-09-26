<?php

namespace App\UserModule\Presenters;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

class InvitationPresenter extends Presenter
{

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

        $form->addText('token', 'Registrační kód pozvánky', null, 15);
        
        $form->addSubmit('send', 'Zkontrolovat kód')
                ->setHtmlId('invitation-process-button');
                //->setDisabled();
        
        $form->onSuccess[] = [$this, 'processInvitation'];

        return $form;
    }

    public function processInvitation(Form $form, $values)
    {
        $form->addError('this ain\'t working yet');
        return;
    }
}