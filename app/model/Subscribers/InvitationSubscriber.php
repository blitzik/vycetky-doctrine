<?php

namespace App\Model\Subscribers;

use App\Model\Subscribers\Results\IResultObject;
use App\Model\Domain\Entities\Invitation;
use App\Model\Facades\InvitationsFacade;
use Kdyby\Events\Subscriber;
use Nette\Mail\SendException;
use Nette\Object;
use Tracy\Debugger;

class InvitationSubscriber extends Object implements Subscriber
{
    /** @var InvitationsFacade  */
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
            'App\Model\Facades\InvitationsFacade::onInvitationCreation'
        ];
    }

    public function onInvitationCreation(
        Invitation $invitation,
        IResultObject $validationObject
    ) {
        try {
            $this->invitationsFacade->sendInvitation($invitation);
        } catch (SendException $e) {
            $validationObject->addError(
                'Registrační pozvánku se nepodařilo odeslat.',
                'warning'
            );

            Debugger::log($e);
        }
    }
}