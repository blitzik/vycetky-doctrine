<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\EntityManager;
use \Exceptions\Runtime;
use Nette\Object;

class UsersWriter extends Object
{
    /** @var array */
    public $onError = [];

    /** @var EntityManager  */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Exception
     */
    public function saveUser(User $user)
    {
        try {
            $this->em->persist($user)->flush();
            return $user;

        } catch (\Exception $e) {
            $this->onError(sprintf('Saving of user "%s" #id(%s) failed', $user->username, $user->getId()), $e, self::class);

            throw $e;
        }
    }
}