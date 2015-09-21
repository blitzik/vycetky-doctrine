<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\Invitation;
use App\Model\Query\InvitationsQuery;
use Exceptions\Runtime\InvitationNotFoundException;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class InvitationsReader extends Object
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $invitationsRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;

        $this->invitationsRepository = $entityManager->getRepository(Invitation::class);
    }

    /**
     * @param InvitationsQuery $invitationsQuery
     * @return mixed
     * @throws InvitationNotFoundException
     */
    public function fetchInvitation(InvitationsQuery $invitationsQuery)
    {
        $result = $this->invitationsRepository->fetchOne($invitationsQuery);
        if ($result['invitation'] === null) {
            throw new InvitationNotFoundException;
        }

        return $result;
    }

    /**
     * @param InvitationsQuery $invitationsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchInvitations(InvitationsQuery $invitationsQuery)
    {
        return $this->invitationsRepository->fetch($invitationsQuery);
    }

}