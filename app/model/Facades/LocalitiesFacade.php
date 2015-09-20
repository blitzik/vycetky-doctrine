<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Locality;
use App\Model\Services\LocalitiesService;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\Arrays;
use Nette\Utils\Validators;

class LocalitiesFacade extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LocalitiesService
     */
    private $localityService;

    public function __construct(
        EntityManager $entityManager,
        LocalitiesService $localityService
    ) {
        $this->em = $entityManager;

        $this->localityService = $localityService;
    }

    /**
     *
     * @param string $localityName
     * @param int $limit
     * @param \App\Model\Domain\Entities\User| $user
     * @return string Localities
     */
    public function findLocalitiesForAutocomplete(
        $localityName,
        $limit,
        \App\Model\Domain\Entities\User $user
    ) {
        Validators::assert($localityName, 'string');
        Validators::assert($limit, 'numericint:0..');

        $localities = $this->em->createQuery(
            'SELECT l.id, l.name FROM ' .Locality::class. ' l
             INNER JOIN l.users u
             WHERE l.name LIKE COLLATE(:name, utf8_czech_ci) AND u.id = :userID'
        )->setMaxResults($limit)
         ->setParameters([
            'name' => '%'.$localityName.'%',
            'userID' => $user->getId()
        ])->getArrayResult();

        return Arrays::associate($localities, 'id=name');
    }
}