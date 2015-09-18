<?php

namespace App\Model\Services\Readers;

use Exceptions\Runtime\ListingItemNotFoundException;
use App\Model\Services\Writers\ListingItemWriter;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Query\ListingItemsQuery;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
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

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemNotFoundException
     */
    public function getPreviousItem(ListingItem $listingItem)
    {
        $previousItemQuery = new ListingItemsQuery();
        $previousItemQuery->byListing($listingItem->getListing())
                          ->byDay($listingItem->day + ListingItemWriter::WRITE_UP);

        return $this->fetchListingItem($previousItemQuery);
    }

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemNotFoundException
     */
    public function getNextItem(ListingItem $listingItem)
    {
        $nextItemQuery = new ListingItemsQuery();
        $nextItemQuery->byListing($listingItem->getListing())
                      ->byDay($listingItem->day + ListingItemWriter::WRITE_DOWN);

        return $this->fetchListingItem($nextItemQuery);
    }
}