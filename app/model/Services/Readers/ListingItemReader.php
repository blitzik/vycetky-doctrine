<?php

namespace App\Model\Services\Readers;

use App\Model\Domain\Entities\ListingItem;
use App\Model\Query\ListingItemsQuery;
use Exceptions\Runtime\ListingItemNotFoundException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class ListingItemReader extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $listingItemRepository;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;

        $this->listingItemRepository = $this->em->getRepository(ListingItem::class);
    }

    /**
     * @param ListingItemsQuery $listingItemsQuery
     * @return mixed
     * @throws ListingItemNotFoundException
     */
    public function fetchListingItem(ListingItemsQuery $listingItemsQuery)
    {
        $item = $this->listingItemRepository->fetchOne($listingItemsQuery);
        if ($item === null) {
            throw new ListingItemNotFoundException;
        }

        return $item;
    }

    /**
     * @param ListingItemsQuery $listingItemsQuery
     * @return mixed
     */
    public function fetchListingItems(ListingItemsQuery $listingItemsQuery)
    {
        return $this->listingItemRepository->fetch($listingItemsQuery);
    }
}