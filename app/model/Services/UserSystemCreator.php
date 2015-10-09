<?php

namespace App\Model\Services\Users;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\User;
use App\Model\Services\Readers\InvitationsReader;
use App\Model\Services\Readers\UsersReader;
use App\Model\Services\Writers\InvitationsWriter;
use Exceptions\Runtime\DuplicateEmailException;
use Exceptions\Runtime\DuplicateUsernameException;
use Exceptions\Runtime\InvalidUserInvitationEmailException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class UserSystemCreator extends Object
{
    /** @var EntityManager  */
    private $em;
    
    /** @var InvitationsReader  */
    private $invitationsReader;

    /** @var InvitationsWriter  */
    private $invitationsWriter;

    /** @var UsersReader  */
    private $usersReader;

    public function __construct(
        EntityManager $entityManager,
        InvitationsReader $invitationsReader,
        InvitationsWriter $invitationsWriter,
        UsersReader $usersReader
    ) {
        $this->em = $entityManager;
        $this->invitationsReader = $invitationsReader;
        $this->invitationsWriter = $invitationsWriter;
        $this->usersReader = $usersReader;
    }

    /**
     * @param User $newUser
     * @param Invitation $invitation
     * @return User
     * @throws DuplicateUsernameException
     * @throws DuplicateEmailException
     * @throws InvalidUserInvitationEmailException
     */
    public function registerUser(
        User $newUser,
        Invitation $invitation
    ) {
        if ($newUser->email !== $invitation->email) {
            throw new InvalidUserInvitationEmailException;
        }

        $this->em->beginTransaction();

        $user = $this->em->safePersist($newUser);
        if ($user === false) {
            $this->em->rollback();

            // e.g. when two users are trying to register
            // at the same time on the same Invitation
            if ($this->usersReader->isEmailRegistered($newUser->email)) {
                $this->invitationsWriter->removeInvitation($invitation->id);
                throw new DuplicateEmailException;
            }

            if ($this->usersReader->isUsernameRegistered($newUser->username)) {
                throw new DuplicateUsernameException;
            }
        }

        $this->invitationsWriter->removeInvitation($invitation->id);
        $this->em->commit();
        return $user;
    }
}