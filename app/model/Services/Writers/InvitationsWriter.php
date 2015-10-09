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
     * @param int $id
     * @return void
     */
    public function removeInvitation($id)
    {
        $this->em->createQuery(
            'DELETE ' .Invitation::class. ' i WHERE i.id = :id'
        )->execute(['id' => $id]);
    }
}