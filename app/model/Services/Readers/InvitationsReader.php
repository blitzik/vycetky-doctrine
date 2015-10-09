<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\Invitation;
use App\Model\Query\InvitationsQuery;
use Doctrine\ORM\NoResultException;
use Exceptions\Runtime\InvitationNotFoundException;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class InvitationsReader extends Object
{
    /** @var EntityManager  */
    private $em;

    /** @var EntityRepository  */
    private $invitationsRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->invitationsRepository = $entityManager->getRepository(Invitation::class);
    }

    /**
     * @param InvitationsQuery $invitationsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchInvitations(InvitationsQuery $invitationsQuery)
    {
        return $this->invitationsRepository->fetch($invitationsQuery);
    }

    /**
     * @param $id
     * @return Invitation
     * @throws InvitationNotFoundException
     */
    public function getInvitationByID($id)
    {
        $qb = $this->invitationsRepository
                   ->createQueryBuilder('i')
                   ->where('i.id = :id')
                   ->setParameter('id', $id);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new InvitationNotFoundException;
        }
    }

    /**
     * @param string $email
     * @param string|null $token
     * @return Invitation|null
     */
    public function getInvitation($email, $token = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('i')
            ->from(Invitation::class, 'i')
            ->where('i.email = :email')
            ->setParameter('email', $email);

        if (isset($token)) {
            $qb->andWhere('i.token = :token')->setParameter('token', $token);
        }

        try {
            $invitation = $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $invitation;
    }

}