<?php

namespace App\Model\Services\Managers;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Query\UsersQuery;
use Doctrine\ORM\NoResultException;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\Validators;

class InvitationsManager extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $invitationRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->invitationRepository = $this->em->getRepository(Invitation::class);
    }

    /**
     * @param string $email
     * @param string|null $token
     * @return Invitation
     * @throws InvitationNotFoundException
     */
    private function getInvitation($email, $token = null)
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
            throw new InvitationNotFoundException;
        }

        return $invitation;
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
        Validators::assert($email, 'email');

        $invitation = $this->getInvitation($email, $token);

        if (!$this->isInvitationTimeValid($invitation)) {
            $this->em->remove($invitation)->flush();
            throw new InvitationExpiredException;
        }

        return $invitation;
    }

    /**
     * @param string $email
     * @return Invitation
     * @throws InvitationAlreadyExistsException
     * @throws UserAlreadyExistsException
     */
    public function createInvitation($email)
    {
        Validators::assert($email, 'email');

        $count = $this->em
                      ->getRepository(User::class)
                      ->fetch((new UsersQuery())->byEmail($email))->count();
        if ($count > 0) {
            throw new UserAlreadyExistsException;
        }

        $invitation = new Invitation(
            $email,
            (new \DateTime)->modify('+1 week')
        );

        $inv = $this->em->safePersist($invitation);
        if ($inv === false) {
            $existingInvitation = $this->getInvitation($email);
            if ($this->isInvitationTimeValid($existingInvitation)) {
                throw new InvitationAlreadyExistsException;
            } else {
                $this->em->remove($existingInvitation);
                $this->em->persist($invitation)->flush();
                $inv = $invitation;
            }
        }

        return $inv;
    }

    /**
     * @param Invitation $invitation
     */
    public function removeInvitation(Invitation $invitation)
    {
        $this->em->remove($invitation)->flush();
    }

    /**
     * @param Invitation $invitation
     * @return boolean TRUE - valid; FALSE - invalid
     */
    private function isInvitationTimeValid(Invitation $invitation)
    {
        $currentDate = new \DateTime;
        if ($currentDate > $invitation->validity) {
            return false;
        }

        return true;
    }
}