<?php

namespace App\Model\Services\Managers;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Query\UsersQuery;
use App\Model\Services\InvitationsSender;
use Doctrine\ORM\NoResultException;
use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Runtime\InvitationExpiredException;
use Exceptions\Runtime\InvitationNotFoundException;
use Exceptions\Runtime\UserAlreadyExistsException;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\InvalidStateException;
use Nette\Object;
use Nette\Utils\Validators;
use Tracy\Debugger;

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

    /**
     * @var InvitationsSender
     */
    private $invitationsSender;


    public function __construct(
        EntityManager $entityManager,
        InvitationsSender $invitationsSender
    ) {
        $this->em = $entityManager;
        $this->invitationsSender = $invitationsSender;

        $this->invitationRepository = $this->em->getRepository(Invitation::class);
    }

    /**
     * @param Invitation $invitation
     * @return void
     */
    private function updateLastSendingTime(Invitation $invitation)
    {
        $invitation->setLastSendingTime();
        $this->em->persist($invitation)->flush();
    }

    /**
     * Sends an E-mail
     *
     * @param Invitation $invitation
     * @throws InvitationExpiredException
     * @throws InvalidStateException
     * @return void
     */
    public function sendInvitation($invitation)
    {
        try {
            $this->invitationsSender->sendInvitation($invitation);
            $this->updateLastSendingTime($invitation);
        } catch (InvalidStateException $e) {
            Debugger::log($e);
            throw $e;
        }
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

        if (!$invitation->isActive()) {
            $this->em->remove($invitation)->flush();
            throw new InvitationExpiredException;
        }

        return $invitation;
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
        Validators::assert($email, 'email');

        $count = $this->em
                      ->getRepository(User::class)
                      ->fetch((new UsersQuery())->byEmail($email))->count();
        if ($count > 0) {
            throw new UserAlreadyExistsException;
        }

        $invitation = new Invitation($email, $sender);

        $inv = $this->em->safePersist($invitation);
        if ($inv === false) {
            $existingInvitation = $this->getInvitation($email);
            if ($existingInvitation->isActive()) {
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
     * @param int $id
     */
    public function removeInvitation($id)
    {
        $this->em->createQuery(
            'DELETE FROM ' .Invitation::class. ' i
             WHERE i.id = :id'
        )->execute(['id' => $id]);
    }


}