<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\Invitation;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class InvitationsWriter extends Object
{
    /** @var EntityManager  */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Uses safePersist method of EntityManager
     *
     * @param Invitation $invitation
     * @return Invitation|null
     */
    public function saveInvitation(Invitation $invitation)
    {
        return $this->em->safePersist($invitation);
    }

    /**
     * @param string|Invitation $invitation Invitation's E-mail or instance of invitation
     * @return void
     */
    public function removeInvitation($invitation)
    {
        $this->em->createQuery(
            'DELETE ' .Invitation::class. ' i WHERE i = :invitation'
        )->execute(['invitation' => $invitation]);
    }
}