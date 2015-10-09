<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use App\Model\Subscribers\Results\ResultObject;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationCreationAttemptException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Nette\Application\UI\Form;
use Nette\Security\User;

class InvitationGenerationControl extends BaseComponent
{
    /** @var array */
    public $onInvitationCreation = [];

    /**  @var InvitationsFacade */
    private $invitationsFacade;

    /** @var User */
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

        $invitation = new Invitation(
            $value['email'],
            $this->user->getIdentity() // todo - security\User tu nema co delat
        );
        try {
            /** @var ResultObject $resultObject */
            $resultObject = $this->invitationsFacade
                                 ->createInvitation($invitation);

            $this->flashMessage(
                'Registrační pozvánka byla vytvořena.',
                'success'
            );

            if (!$resultObject->hasNoErrors()) {
                $error = $resultObject->getFirstError();
                $this->flashMessage($error['message'], $error['type']);
            }

        } catch (InvitationCreationAttemptException $ca) {
            $this->flashMessage(
                'Pozvánku nebyla vytvořena. Zkuste akci opakovat později.',
                'error'
            );

        } catch (UserAlreadyExistsException $uae) {
            $form->addError(
                'Pozvánku nelze odeslat. Uživatel s E-Mailem ' . $value['email'] . ' je již zaregistrován.'
            );
            return;

        } catch (InvitationAlreadyExistsException $iae) {
            $form->addError(
                'Někdo jiný již odeslal pozvánku uživateli s E-mailem ' .$value['email']
            );
            return;

        } catch (DBALException $e) {
            $this->flashMessage(
                'Při vytváření pozvánky došlo k chybě. Zkuste akci opakovat později.',
                'error'
            );
        }

        $this->redirect('this');
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->render();
    }
}