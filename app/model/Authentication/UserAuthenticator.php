<?php

namespace App\Model\Authentication;

use App\Model\Services\Readers\UsersReader;
use Exceptions\Runtime\InaccessibleAccountException;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Http\IRequest;
use Nette\Object;

class UserAuthenticator extends Object implements IAuthenticator
{
    /** @var array */
    public $onLoggedIn = [];

    /**  @var UsersReader */
    private $usersReader;

    /** @var IRequest */
    private $httpRequest;

    public function __construct(
        UsersReader $usersReader,
        IRequest $httpRequest
    ) {
        $this->httpRequest = $httpRequest;
        $this->usersReader = $usersReader;
    }

    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     * @return IIdentity
     * @throws AuthenticationException
     * @throws InaccessibleAccountException
     */
    public function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;

        $user = $this->usersReader
                     ->getUserByEmail($email);

        if ($user === null) {
            throw new AuthenticationException('Zadali jste špatný email.');
        }

        if (!Passwords::verify($password, $user->password)) {
            throw new AuthenticationException('Zadali jste špatné heslo.');

        } elseif (Passwords::needsRehash($user->password)) {

            $user->password = Passwords::hash($password);
        }

        if (!$user->isUserAccountAccessible()) {
            throw new InaccessibleAccountException; // user is banned
        }

        $this->onLoggedIn($user);

        return new FakeIdentity($user->getId(), get_class($user));
    }
}