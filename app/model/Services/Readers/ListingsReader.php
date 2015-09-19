<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\Listing;
use App\Model\Query\ListingsQuery;
use Exceptions\Runtime\ListingNotFoundException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class ListingsReader extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $listingsRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->listingsRepository = $this->em->getRepository(Listing::class);
    }

    /**
     * @param ListingsQuery $listingsQuery
     * @return mixed
     * @throws ListingNotFoundException
     */
    public function fetchListing(ListingsQuery $listingsQuery)
    {
        $listingData = $this->listingsRepository->fetchOne($listingsQuery);
        if (isset($listingData['listing']) and $listingData['listing'] === null) {
            throw new ListingNotFoundException;
        }

        return $listingData;
    }

    /**
     * @param ListingsQuery $listingsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchListings(ListingsQuery $listingsQuery)
    {
        return $this->listingsRepository->fetch($listingsQuery);
    }
}