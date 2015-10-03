<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\EntityManager;
use \Exceptions\Runtime;
use Nette\Object;

class UsersWriter extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
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
}