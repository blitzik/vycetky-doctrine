<?php

namespace App\Model\Listeners;

use App\Model\Domain\Entities\User;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Nette\Http\IRequest;
use Nette\Object;

class AuthenticationListener extends Object implements Subscriber
{
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
        $this->entityManager = $entityManager;
        $this->httpRequest = $httpRequest;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'App\Model\Authentication\UserAuthenticator::onLoggedIn'
        ];
    }

    public function onLoggedIn(User $user)
    {
        $user->setLastLogin(new \DateTime('now'));
        $user->setLastIP($this->httpRequest->getRemoteAddress());

        $this->entityManager->persist($user)->flush();
    }
}