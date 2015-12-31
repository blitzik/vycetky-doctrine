<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Invitation;
use App\Model\Query\InvitationsQuery;
use App\Model\Services\InvitationHandler;
use App\Model\Services\InvitationsSender;
use App\Model\Services\Readers\InvitationsReader;
use App\Model\Services\Readers\UsersReader;
use App\Model\Services\Writers\InvitationsWriter;
use App\Model\Subscribers\Results\EntityResultObject;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Nette\Mail\SendException;
use Nette\Utils\Validators;
use Nette\Object;

class InvitationsFacade extends Object
{
    /** @var array  */
    public $onInvitationCreation = [];

    /** @var InvitationsReader  */
    private $invitationsReader;

    /** @var InvitationsSender  */
    private $invitationsSender;

    /** @var InvitationHandler  */
    private $invitationsHandler;

    /** @var InvitationsWriter  */
    private $invitationsWriter;

    /** @var UsersReader  */
    private $usersReader;


    public function __construct(
        InvitationsReader $invitationsReader,
        InvitationsWriter $invitationsWriter,
        InvitationsSender $invitationsSender,
        InvitationHandler $invitationsHandler,
        UsersReader $usersReader
    ) {
        $this->invitationsReader = $invitationsReader;
        $this->invitationsWriter = $invitationsWriter;
        $this->invitationsSender = $invitationsSender;
        $this->usersReader = $usersReader;
        $this->invitationsHandler = $invitationsHandler;
    }


    /**
     * @param InvitationsQuery $invitationsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchInvitations(InvitationsQuery $invitationsQuery)
    {
        return $this->invitationsReader->fetchInvitations($invitationsQuery);
    }


    /**
     * @param string $email
     * @param string $token
     * @return Invitation
     * @throws InvitationNotFoundException
     * @throws InvitationExpiredException
     */
    public function checkInvitation($email, $token)
    {
        $inv = $this->getInvitation($email, $token);
        try {
            $this->checkInvitationState($inv);
        } catch (InvitationExpiredException $e) {
            $this->invitationsWriter->removeInvitation($inv);
            throw $e;
        }

        return $inv;
    }


    /**
     * @param string $email
     * @param string $token
     * @return Invitation|null
     * @throws InvitationNotFoundException
     */
    public function getInvitation($email, $token)
    {
        Validators::assert($email, 'email');

        $invitation = $this->invitationsReader->getInvitation($email, $token);
        if ($invitation === null) {
            throw new InvitationNotFoundException;
        }

        return $invitation;
    }


    /**
     * @param Invitation $invitation
     */
    private function checkInvitationState(Invitation $invitation)
    {
        if (!$invitation->isActive()) {
            throw new InvitationExpiredException;
        }
    }


    /**
     * @param Invitation $invitation
     * @return EntityResultObject
     * @throws InvitationAlreadyExistsException
     * @throws UserAlreadyExistsException
     */
    public function createInvitation(Invitation $invitation)
    {
        $isEmailRegistered = $this->usersReader
                                  ->isEmailRegistered($invitation->email);
        if ($isEmailRegistered === true) {
            throw new UserAlreadyExistsException;
        }

        $this->invitationsHandler->process($invitation);

        $resultObject = new EntityResultObject($invitation);
        $this->onInvitationCreation($invitation, $resultObject);

        return $resultObject;
    }


    /**
     * @param string|Invitation $invitation Invitation's E-mail or instance of Invitation
     */
    public function removeInvitation($invitation)
    {
        $this->invitationsWriter->removeInvitation($invitation);
    }


    /**
     * @param Invitation|string $invitation
     * @throws InvitationNotFoundException
     * @throws InvitationExpiredException
     * @throws SendException
     * @return void
     */
    public function sendInvitation($invitation)
    {
        if (Validators::is($invitation, 'string')) {
            $invitation = $this->invitationsReader->getInvitationByEmail($invitation);
        }
        $this->checkInvitationState($invitation);

        $this->invitationsSender->sendInvitation($invitation);

        $invitation->setLastSendingTime();
        $this->invitationsWriter->saveInvitation($invitation);
    }

}