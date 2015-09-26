<?php

namespace App\Model\Authentication;

use App\Model\Domain\Entities\User;
use App\Model\Facades\UsersFacade;
use App\Model\Query\UsersQuery;
use Doctrine\ORM\NoResultException;
use Kdyby\Doctrine\EntityManager;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Http\IRequest;
use Nette\Object;

class UserAuthenticator extends Object implements IAuthenticator
{
    /**
     * @var array
     */
    public $onLoggedIn = [];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var IRequest
     */
    private $httpRequest;

    public function __construct(
        EntityManager $entityManager,
        IRequest $httpRequest
    ) {
        $this->httpRequest = $httpRequest;
        $this->entityManager = $entityManager;
    }

    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     * @return IIdentity
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;

        $user = $this->entityManager
                     ->getRepository(User::class)
                     ->fetchOne((new UsersQuery())->byEmail($email));

        if ($user === null) {
            throw new AuthenticationException('Zadali jste špatný email.');
        }

        if (!Passwords::verify($password, $user->password)) {
            throw new AuthenticationException('Zadali jste špatné heslo.');

        } elseif (Passwords::needsRehash($user->password)) {

            $user->password = Passwords::hash($password);
        }

        $this->onLoggedIn($user);

        return new FakeIdentity($user->getId(), get_class($user));
    }
}