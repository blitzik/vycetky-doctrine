<?php

namespace App\Model\Services;

use App\Model\Domain\Entities\Invitation;
use App\Model\Services\Readers\InvitationsReader;
use App\Model\Services\Writers\InvitationsWriter;
use App\Model\Subscribers\Validation\InvitationResultObject;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationCreationAttemptException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;

class InvitationHandler extends Object
{
    public $onInvitationCreation = [];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var InvitationsReader
     */
    private $invitationsReader;

    /**
     * @var InvitationsWriter
     */
    private $invitationsWriter;

    /**
     * @var int
     */
    private $creationAttempts = 0;

    public function __construct(
        EntityManager $entityManager,
        InvitationsReader $invitationsReader,
        InvitationsWriter $invitationsWriter
    ) {
        $this->entityManager = $entityManager;
        $this->invitationsReader = $invitationsReader;
        $this->invitationsWriter = $invitationsWriter;
    }

    /**
     * @param Invitation $invitation
     * @return InvitationResultObject
     * @throws InvitationAlreadyExistsException
     * @throws InvitationCreationAttemptException
     * @throws DBALException
     */
    public function process(Invitation $invitation)
    {
        try {
            $this->entityManager->beginTransaction();
                $inv = $this->createInvitation($invitation);
            $this->entityManager->commit();
        } catch (InvitationCreationAttemptException $ca) {
            $this->entityManager->rollback();

            Debugger::log($ca, Debugger::ERROR);
            throw $ca;
        } catch (InvitationAlreadyExistsException $ae) {
            $this->entityManager->rollback();
            throw $ae;
        }

        $validationObject = new InvitationResultObject($inv);
        $this->onInvitationCreation($invitation, $validationObject);

        return $validationObject;
    }

    /**
     * @param Invitation $invitation
     * @return Invitation
     * @throws InvitationAlreadyExistsException
     * @throws InvitationCreationAttemptException
     */
    private function createInvitation(Invitation $invitation)
    {
        if ($this->creationAttempts > 5) { // should prevent infinite recursion
            throw new InvitationCreationAttemptException;
        }
        ++$this->creationAttempts;

        /** @var Invitation $invitation */
        $inv = $this->invitationsWriter->saveInvitation($invitation);
        if ($inv === false) { // already exists
            $fetchedInv = $this->invitationsReader
                               ->getInvitation($invitation->email);

            if ($fetchedInv === null) {
                // someone's removed or caused removal of invitation
                return $this->createInvitation($invitation);
            } else {
                if ($fetchedInv->isActive()) {
                    throw new InvitationAlreadyExistsException;
                } else {
                    $this->invitationsWriter->removeInvitation($fetchedInv->id);
                    return $this->createInvitation($invitation);
                }
            }
        }

        return $inv;
    }
}