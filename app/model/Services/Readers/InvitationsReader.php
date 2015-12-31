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
    /** @var array */
    public $onInfo = [];

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
     * @param string $email
     * @return Invitation
     * @throws InvitationNotFoundException
     */
    public function getInvitationByEmail($email)
    {
        $qb = $this->invitationsRepository
                   ->createQueryBuilder('i')
                   ->where('i.email = :email') // todo WTF? there should be email and NOT id
                   ->setParameter('email', $email);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            $this->onInfo("Email: $email NOT found. [getInvitationByEmail]", $e, self::class);
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
        $qb->select('i, partial s.{id, username}')
            ->from(Invitation::class, 'i')
            ->leftJoin('i.sender', 's')
            ->where('i.email = :email')
            ->setParameter('email', $email);

        if (isset($token)) {
            $qb->andWhere('i.token = :token')->setParameter('token', $token);
        }

        try {
            $invitation = $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            $this->onInfo("Email: $email NOT found. [getInvitationByEmail]", $e, self::class);
            return null;
        }

        return $invitation;
    }

}