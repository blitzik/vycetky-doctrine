<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Query\InvitationsQuery;
use App\Model\Services\Managers\InvitationsManager;
use App\Model\Services\Readers\InvitationsReader;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Nette\InvalidStateException;
use Nette\Object;

class InvitationsFacade extends Object
{
    /**
     * @var InvitationsReader
     */
    private $invitationsReader;

    /**
     * @var InvitationsManager
     */
    private $invitationsManager;

    public function __construct(
        InvitationsReader $invitationsReader,
        InvitationsManager $invitationsManager
    ) {
        $this->invitationsReader = $invitationsReader;
        $this->invitationsManager = $invitationsManager;
    }

    /**
     * @param InvitationsQuery $invitationsQuery
     * @return Invitation
     * @throws InvitationNotFoundException
     */
    public function fetchInvitation(InvitationsQuery $invitationsQuery)
    {
        return $this->invitationsReader->fetchInvitation($invitationsQuery);
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
        return $this->invitationsManager->checkInvitation($email, $token);
    }

    /**
     * @param string $email
     * @param User $sender
     * @return Invitation
     * @throws InvitationAlreadyExistsException
     * @throws UserAlreadyExistsException
     */
    public function createInvitation($email, User $sender)
    {
        return $this->invitationsManager->createInvitation($email, $sender);
    }

    /**
     * @param int $id
     */
    public function removeInvitation($id)
    {
        $this->invitationsManager->removeInvitation($id);
    }

    /**
     * @param Invitation $invitation
     * @throws InvitationExpiredException
     * @throws InvalidStateException
     */
    public function sendInvitation(Invitation $invitation)
    {
        $this->invitationsManager->sendInvitation($invitation);
    }

}