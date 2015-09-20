<?php

namespace App\Model\Services\Managers;

use App\Model\Services\Readers\UsersReader;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\Listing;
use Kdyby\Doctrine\EntityRepository;
use App\Model\Domain\Entities\User;
use App\Model\Query\ListingsQuery;
use Kdyby\Doctrine\EntityManager;
use App\Model\Query\UsersQuery;
use Nette\Utils\Validators;
use \Exceptions\Runtime;
use Nette\Object;

class UsersManager extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $listingRepository;

    /**
     * @var EntityRepository
     */
    private $userRepository;

    /**
     * @var InvitationsManager
     */
    private $invitationsManager;

    /**
     * @var UsersReader
     */
    private $usersReader;

    /**
     * @var \Nette\Http\IRequest
     */
    private $httpRequest;

    public function __construct(
        InvitationsManager $invitationsManager,
        EntityManager $entityManager,
        UsersReader $usersReader,
        \Nette\Http\IRequest $httpRequest
    ) {
        $this->invitationsManager = $invitationsManager;
        $this->em = $entityManager;
        $this->usersReader = $usersReader;
        $this->httpRequest = $httpRequest;

        $this->userRepository = $this->em->getRepository(User::class);
        $this->listingRepository = $this->em->getRepository(Listing::class);
    }

    /**
     * @param User $user
     * @return User
     */
    public function saveUser(User $user)
    {
        $this->em->persist($user)->flush();

        return $user;
    }

    /**
     *
     * @param string $email
     * @return User
     * @throws Runtime\UserNotFoundException
     */
    public function resetPassword($email)
    {
        $user = $this->usersReader
                     ->fetchUser((new UsersQuery())
                                 ->byEmail($email)
                     );
        $user->createToken();

        $this->em->persist($user)->flush();

        return $user;
    }

    /**
     * @param User $user
     * @param Invitation $invitation
     * @return User
     * @throws Runtime\DuplicateUsernameException
     * @throws Runtime\DuplicateEmailException
     * @throws Runtime\InvitationNotFoundException
     * @throws Runtime\InvitationExpiredException
     */
    public function registerNewUser(
        User $user,
        Invitation $invitation
    ) {
        $this->invitationsManager->checkInvitation($user->email, $invitation->token);

        try {
            $this->em->beginTransaction();

                $this->em->persist($user);
                $this->em->remove($invitation);

                $this->em->flush();

            $this->em->commit();

        } catch (UniqueConstraintViolationException $e) {

            $username = $this->userRepository->findOneBy(['username' => $user->username]);
            if ($username !== null) {
                $this->em->rollback();
                throw new Runtime\DuplicateUsernameException;
            }

            $email = $this->userRepository->findOneBy(['email' => $user->email]);
            if ($email !== null) {
                $this->em->rollback();
                throw new Runtime\DuplicateEmailException;
            }
        }

        return $user;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getTotalWorkedStatistics(User $user)
    {
        return $this->listingRepository->fetch(
            (new ListingsQuery())
            ->resetSelect()
            ->withNumberOfWorkedDays()
            ->withTotalWorkedHours()
            ->byUser($user)
        )->toArray()[0];
    }
}