<?php

namespace App\Model\Facades;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\Listing;
use Doctrine\DBAL\Types\ConversionException;
use Kdyby\Doctrine\EntityRepository;
use App\Model\Domain\Entities\User;
use App\Model\Query\ListingsQuery;
use Kdyby\Doctrine\EntityManager;
use App\Model\Query\UsersQuery;
use Nette\Utils\Validators;
use App\Model\Repositories;
use \Exceptions\Runtime;
use Nette\Object;

class UserManager extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Repositories\WorkedHoursRepository
     */
    private $workingHourRepository;

    /**
     * @var EntityRepository
     */
    private $invitationRepository;

    /**
     * @var EntityRepository
     */
    private $listingRepository;

    /**
     * @var EntityRepository
     */
    private $userRepository;

    /**
     * @var \Transaction
     */
    private $transaction;

    /**
     * @var \Nette\Http\IRequest
     */
    private $httpRequest;

    public function __construct(
        EntityManager $entityManager,
        Repositories\WorkedHoursRepository $workingHourRepository,
        Repositories\InvitationRepository $invitationRepository,
        Repositories\UserRepository $userRepository,
        \Nette\Http\IRequest $httpRequest,
        \Transaction $transaction
    ) {
        $this->em = $entityManager;
        $this->userRepository = $this->em->getRepository(User::class);
        $this->listingRepository = $this->em->getRepository(Listing::class);
        $this->invitationRepository = $this->em->getRepository(Invitation::class);

        $this->workingHourRepository = $workingHourRepository;
        $this->transaction = $transaction;
        $this->httpRequest = $httpRequest;
    }

    /**
     * @param string $email
     * @param string $token
     * @return Invitation
     * @throws Runtime\InvitationNotFoundException
     * @throws Runtime\InvitationExpiredException
     * @throws Runtime\InvitationTokenMatchException
     */
    public function checkInvitation($email, $token)
    {
        Validators::assert($email, 'email');

        $invitation = $this->em->getRepository(Invitation::class)
                           ->findOneBy(['token' => $token, 'email' => $email]);

        if ($invitation === null) {
            throw new Runtime\InvitationNotFoundException;
        }

        if (!$this->isInvitationTimeValid($invitation)) {
            $this->removeInvitation($invitation);
            throw new Runtime\InvitationExpiredException;
        }

        return $invitation;
    }

    /**
     *
     * @param Invitation $invitation
     * @return void
     */
    public function removeInvitation(Invitation $invitation)
    {
        $this->em->remove($invitation)->flush();
    }

    /**
     *
     * @param User $user
     * @return void
     */
    public function resetToken(User $user)
    {
        $user->resetToken();
        $this->userRepository->persist($user);
    }

    /**
     * @param User $user
     * @return int User ID
     */
    public function saveUser(User $user)
    {
        return $this->userRepository->persist($user);
    }

    /**
     *
     * @param string $email
     * @return User
     */
    public function findUserByEmail($email)
    {
        return $this->userRepository
                    ->fetchOne(
                        (new UsersQuery())->byEmail($email)
                    );
    }

    /**
     * @param $userID
     * @return User
     * @throws Runtime\UserNotFoundException
     */
    public function getUserByID($userID)
    {
        return $this->userRepository->getUserByID($userID);
    }

    /**
     *
     * @param string $email
     * @return User
     * @throws Runtime\UserNotFoundException
     */
    public function resetPassword($email)
    {
        $user = $this->findUserByEmail($email);

        $user->createToken();

        $this->userRepository->persist($user);

        return $user;
    }

    /**
     * @param string $email
     * @return Invitation
     * @throws Runtime\InvitationAlreadyExistsException
     * @throws Runtime\UserAlreadyExistsException
     */
    public function createInvitation($email)
    {
        Validators::assert($email, 'email');

        $count = $this->userRepository->fetch((new UsersQuery())->byEmail($email))->count();
        if ($count > 0) {
            throw new Runtime\UserAlreadyExistsException;
        }

        $invitation = new Invitation(
            $email,
            (new \DateTime)->modify('+1 week')
        );

        $result = $this->em->safePersist($invitation);
        if ($result === false) {
            $existingInvitation = $this->invitationRepository->findOneBy(['email' => $email]);
            if ($this->isInvitationTimeValid($existingInvitation)) {
                throw new Runtime\InvitationAlreadyExistsException;
            } else {
                $this->removeInvitation($existingInvitation);
                $this->em->persist($invitation)->flush();
            }
        }

        return $invitation;
    }

    /**
     * @param User $user
     * @param Invitation $invitation
     * @return void
     * @throws Runtime\DuplicateUsernameException
     * @throws Runtime\DuplicateEmailException
     * @throws Runtime\InvitationNotFoundException
     * @throws Runtime\InvitationExpiredException
     * @throws Runtime\InvitationTokenMatchException
     */
    public function registerNewUser(
        User $user,
        Invitation $invitation
    ) {
        $this->checkInvitation($user->email, $invitation->token);

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
                $this->transaction->rollback();
                throw new Runtime\DuplicateEmailException;
            }
        }
    }

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

    /**
     * @param array|null $withoutUsers
     * @return array
     */
    public function findAllUsers(array $withoutUsers = null)
    {
        $users = $this->userRepository->findAllUsers($withoutUsers);

        return $users;
    }
}