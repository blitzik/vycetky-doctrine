<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Locality;
use App\Model\Repositories\LocalityRepository;
use App\Model\Services\LocalityService;
use Kdyby\Doctrine\EntityManager;
use Nette\Utils\Arrays;
use Nette\Utils\Validators;
use Nette\Security\User;

class LocalityFacade extends BaseFacade
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LocalityRepository
     */
    private $localityRepository;

    /**
     * @var LocalityService
     */
    private $localityService;

    public function __construct(
        EntityManager $entityManager,
        LocalityRepository $localityRepository,
        LocalityService $localityService,
        User $user
    ) {
        parent::__construct($user);

        $this->em = $entityManager;

        $this->localityRepository = $localityRepository;
        $this->localityService = $localityService;
        $this->user = $user;
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

    /**
     * @param string|null $localityName
     * @param int $limit
     * @param User|int|null $user
     * @return array
     */
    public function findLocalities($localityName, $limit, $user = null)
    {
        Validators::assert($localityName, 'unicode|null');
        Validators::assert($limit, 'numericint');
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $localities = $this->localityRepository
                                  ->findSimilarByName(
                                      $localityName,
                                      $userID,
                                      $limit
                                  );
    }

    /**
     * @return int
     */
    public function getNumberOfUserLocalities()
    {
        return $this->localityRepository->getNumberOfUserLocalities($this->user->id);
    }

    /**
     * @param User|int|null $user
     * @return array
     */
    public function findAllUserLocalities($user)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->localityRepository->findAllUserLocalities($userID);
    }

    /**
     * @param int $localityID
     */
    public function removeUserLocality($localityID)
    {
        Validators::assert($localityID, 'numericint');

        $this->localityRepository->removeUserLocality($localityID, $this->user->id);
    }

    /**
     * @param array $localitiesIDs
     */
    public function removeLocalities(array $localitiesIDs)
    {
        $this->localityRepository->removeLocalities($localitiesIDs, $this->user->id);
    }
}