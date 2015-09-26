<?php

namespace App\Model\Components;

use App\Model\Facades\InvitationsFacade;
use App\Model\Subscribers\Validation\SubscriberValidationObject;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\User;

class InvitationGenerationControl extends Control
{
    /**
     * @var array
     */
    public $onInvitationCreation = [];

    /**
     * @var InvitationsFacade
     */
    private $invitationsFacade;

    /**
     * @var User
     */
    private $user;


    public function __construct(
        InvitationsFacade $invitationsFacade,
        User $user
    ) {
        $this->invitationsFacade = $invitationsFacade;
        $this->user = $user;
    }

    protected function createComponentSendKeyForm()
    {
        $form = new Form();

        $form->addText('email', 'E-mailová adresa příjemce', 22)
            ->setRequired('Zadejte prosím E-mail, na který se má pozvánka odeslat.')
            ->addRule(Form::EMAIL, 'Zadejte platnou E-Mailovou adresu.');

        $form->addSubmit('send', 'Odeslat pozvánku');

        $form->onSuccess[] = [$this, 'processCreateInvitation'];

        $form->addProtection();

        return $form;
    }

    public function processCreateInvitation(Form $form)
    {
        $value = $form->getValues();

        try {
            $invitation = $this->invitationsFacade
                               ->createInvitation(
                                   $value['email'],
                                   $this->user->getIdentity()
                               );

            $this->flashMessage(
                'Registrační pozvánka byla vytvořena.',
                'success'
            );

        } catch (UserAlreadyExistsException $uae) {
            $this->flashMessage(
                'Pozvánku nelze odeslat. Uživatel s E-Mailem ' . $value['email'] . ' je již zaregistrován.',
                'warning'
            );
            $this->redirect('this');

        } catch (InvitationAlreadyExistsException $iae) {
            $this->flashMessage(
                'Někdo jiný již odeslal pozvánku uživateli s E-mailem ' .$value['email'],
                'warning'
            );
            $this->redirect('this');

        } catch (\Exception $e) {
            $this->flashMessage(
                'Pozvánku se nepodařilo vytvořit. Zkuste akci opakovat později.',
                'warning'
            );
            $this->redirect('this');
        }

        $validationObject = new SubscriberValidationObject();
        $this->onInvitationCreation($invitation, $validationObject);
        if (!$validationObject->isValid()) {
            $error = $validationObject->getFirstError();
            $this->flashMessage($error['message'], $error['type']);
        }

    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');



        $template->render();
    }
}