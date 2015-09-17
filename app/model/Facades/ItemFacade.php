<?php

namespace App\Model\Facades;

use App\Model\Query\ListingItemsQuery;
use App\Model\Services\Managers\ListingItemManager;
use App\Model\Services\Readers\ListingItemReader;
use Exceptions\Runtime\DayExceedCurrentMonthException;
use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\ListingItemNotFoundException;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Listing;
use App\Model\Services\ItemService;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Security\User;

class ItemFacade extends BaseFacade
{
    /**
     * @var array
     */
    public $onListingItemSaving;


    /**
     * @var EntityRepository
     */
    private $listingItemRepository;

    /**
     * @var ListingItemManager
     */
    private $listingItemManager;

    /**
     * @var ListingItemReader
     */
    private $listingItemReader;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ItemService
     */
    private $itemService;

    public function __construct(
        ListingItemManager $listingItemManager,
        ListingItemReader $listingItemReader,
        EntityManager $entityManager,
        ItemService $itemService,
        User $user
    ) {
        parent::__construct($user);

        $this->listingItemManager = $listingItemManager;
        $this->listingItemReader = $listingItemReader;
        $this->em = $entityManager;

        $this->listingItemRepository = $this->em->getRepository(ListingItem::class);

        $this->itemService = $itemService;
    }

    /**
     * @param array $newValues
     * @param ListingItem|null $listingItem
     * @return ListingItem
     * @throws OtherHoursZeroTimeException
     * @throws NegativeResultOfTimeCalcException
     * @throws ShiftEndBeforeStartException
     * @throws ListingItemDayAlreadyExistsException
     * @throws \Exception
     */
    public function saveListingItem(array $newValues, ListingItem $listingItem = null)
    {
        return $this->listingItemManager->saveListingItem($newValues, $listingItem);
    }

    /**
     * @param ListingItemsQuery $listingItemsQuery
     * @return mixed
     * @throws ListingItemNotFoundException
     */
    public function fetchListingItem(ListingItemsQuery $listingItemsQuery)
    {
        return $this->listingItemReader->fetchListingItem($listingItemsQuery);
    }

    /**
     * @param ListingItemsQuery $listingItemsQuery
     * @return mixed
     */
    public function fetchListingItems(ListingItemsQuery $listingItemsQuery)
    {
        return $this->listingItemReader->fetchListingItems($listingItemsQuery);
    }

    /**
     * @param int $day
     * @param Listing $listing
     */
    public function removeListingItem($day, Listing $listing)
    {
        $this->listingItemManager->removeListingItem($day, $listing);
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return mixed
     * @throws DayExceedCurrentMonthException
     * @throws ListingItemNotFoundException
     * @throws \Exception
     */
    public function shiftCopyOfListingItemDown(
        $day,
        Listing $listing
    ) {
        return $this->listingItemManager
                    ->shiftCopyOfListingItemDown($day, $listing);
    }

    /**
     * @param array $listingItems
     * @return array Array of ListingItemDecorators
     */
    public function createListingItemDecoratorsCollection(array $listingItems)
    {
        return $this->itemService->createDecoratorsCollection($listingItems);
    }

    /**
     * @param Listing $listing
     * @return ListingItemDecorator[]
     */
    public function generateEntireTable(
        Listing $listing
    ) {
        $listingItems = $this->em->createQuery(
            'SELECT li, lo, wh FROM ' .ListingItem::class. ' li
             JOIN li.locality lo
             JOIN li.workedHours wh
             WHERE li.listing = :listing'
        )->setParameter('listing', $listing)
         ->getResult();

        $collectionOfDecorators = $this->createListingItemDecoratorsCollection(
            $listingItems
        );

        return $this->itemService->generateListingItemDecoratorsForEntireTable(
            $collectionOfDecorators,
            $listing->getPeriod()
        );
    }

}