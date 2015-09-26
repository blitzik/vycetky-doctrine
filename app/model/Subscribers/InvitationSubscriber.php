<?php

namespace App\Model\Subscribers;

use App\Model\Subscribers\Validation\SubscriberValidationObject;
use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use Kdyby\Events\Subscriber;
use Nette\InvalidStateException;
use Nette\Object;
use Tracy\Debugger;

class InvitationSubscriber extends Object implements Subscriber
{
    /**
     * @var InvitationsFacade
     */
    private $invitationsFacade;

    public function __construct(
        InvitationsFacade $invitationsFacade
    ) {
        $this->invitationsFacade = $invitationsFacade;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'App\Model\Components\InvitationGenerationControl::onInvitationCreation'
        ];
    }

    public function onInvitationCreation(
        Invitation $invitation,
        SubscriberValidationObject $validationObject
    ) {
        try {
            $this->invitationsFacade->sendInvitation($invitation);
        } catch (InvalidStateException $e) {
            $validationObject->addError(
                'Registrační pozvánku se nepodařilo odeslat.',
                'warning'
            );

            Debugger::log($e);
        }
    }
}