<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Services\LocalitiesService;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\Validators;

class LocalitiesFacade extends Object
{
    /** @var EntityManager  */
    private $em;

    /** @var LocalitiesService  */
    private $localityService;

    public function __construct(
        EntityManager $entityManager,
        LocalitiesService $localityService
    ) {
        $this->em = $entityManager;

        $this->localityService = $localityService;
    }

    /**
     * @param string $localityName
     * @param Listing $listing
     * @param int $limit
     * @return array
     */
    public function findLocalitiesForAutocomplete(
        $localityName,
        Listing $listing,
        $limit
    ) {
        Validators::assert($localityName, 'string');
        Validators::assert($limit, 'numericint:0..');

        $localities = $this->em->createQuery(
            'SELECT partial li.{id}, partial l.{id, name} FROM ' .ListingItem::class. ' li
             JOIN li.locality l
             WHERE li.listing = :listing AND l.name LIKE COLLATE(:name, utf8_unicode_ci)
             GROUP BY l.id'
        )->setMaxResults($limit)
         ->setParameters([
            'name' => '%'.$localityName.'%',
            'listing' => $listing
        ])->getArrayResult();

        //dump($localities);
        return array_column(array_column($localities, 'locality'), 'name', 'id');
    }
}