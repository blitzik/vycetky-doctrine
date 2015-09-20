<?php

namespace App\Model\Subscribers;

use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\UsersFacade;
use App\Model\Notifications\EmailNotifier;
use App\Model\Subscribers\Validation\SubscriberValidationObject;
use Kdyby\Events\Subscriber;
use Nette\Application\UI\ITemplate;
use Nette\InvalidStateException;
use Nette\Object;
use Nette\Security\User;
use Tracy\Debugger;

class InvitationSubscriber extends Object implements Subscriber
{
    /**
     * @var string
     */
    private $systemEmail;

    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    /**
     * @var UsersFacade
     */
    private $usersFacade;

    /**
     * @var User
     */
    private $user;

    public function __construct(
        EmailNotifier $emailNotifier,
        UsersFacade $usersFacade,
        User $user
    ) {
        $this->emailNotifier = $emailNotifier;
        $this->usersFacade = $usersFacade;
        $this->user = $user;
    }

    /**
     * @param string $systemEmail
     */
    public function setSystemEmail($systemEmail)
    {
        $this->systemEmail = $systemEmail;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'App\FrontModule\Presenters\ProfilePresenter::onInvitationCreation'
        ];
    }

    public function onInvitationCreation(
        Invitation $invitation,
        SubscriberValidationObject $validationObject
    ) {
        try {
            $this->emailNotifier->send(
                'Výčetkový systém <' . $this->systemEmail . '>',
                $invitation->email,
                function (ITemplate $template, Invitation $invitation, $senderName) {
                    $template->setFile(__DIR__ . '/../../model/Notifications/templates/invitation.latte');
                    $template->invitation = $invitation;
                    $template->username = $senderName;

                },
                [$invitation, $this->user->getIdentity()->username]
            );
        } catch (InvalidStateException $e) {
            $this->usersFacade->removeInvitation($invitation);

            $validationObject->addError(
                'Registrační pozvánku nebylo možné odeslat.
                 Zkuste to prosím později.',
                'error'
            );

            Debugger::log($e);
        }
    }
}