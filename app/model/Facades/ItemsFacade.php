<?php

namespace App\Model\Facades;

use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\ListingItemNotFoundException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use App\Model\Services\Managers\ListingItemsManager;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Writers\ListingItemsWriter;
use Exceptions\Runtime\ShiftItemDownException;
use Exceptions\Runtime\ShiftItemUpException;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Listing;
use App\Model\Query\ListingItemsQuery;
use Kdyby\Doctrine\EntityRepository;
use App\Model\Services\ItemsService;
use Kdyby\Doctrine\EntityManager;
use Nette\Security\User;

class ItemsFacade extends BaseFacade
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
     * @var ListingItemsManager
     */
    private $listingItemsManager;

    /**
     * @var ListingItemsReader
     */
    private $listingItemsReader;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ItemsService
     */
    private $itemsService;

    public function __construct(
        ListingItemsManager $listingItemManager,
        ListingItemsReader $listingItemReader,
        EntityManager $entityManager,
        ItemsService $itemService,
        User $user
    ) {
        parent::__construct($user);

        $this->listingItemsManager = $listingItemManager;
        $this->listingItemsReader = $listingItemReader;
        $this->em = $entityManager;

        $this->listingItemRepository = $this->em->getRepository(ListingItem::class);

        $this->itemsService = $itemService;
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
        $item = $this->listingItemsManager->prepareListingItemByFormsData($newValues, $listingItem);

        return $this->listingItemsManager->saveListingItem($item);
    }

    /**
     * @param ListingItemsQuery $listingItemsQuery
     * @return mixed
     * @throws ListingItemNotFoundException
     */
    public function fetchListingItem(ListingItemsQuery $listingItemsQuery)
    {
        return $this->listingItemsReader->fetchListingItem($listingItemsQuery);
    }

    /**
     * @param ListingItemsQuery $listingItemsQuery
     * @return mixed
     */
    public function fetchListingItems(ListingItemsQuery $listingItemsQuery)
    {
        return $this->listingItemsReader->fetchListingItems($listingItemsQuery);
    }

    /**
     * @param int $day
     * @param Listing $listing
     */
    public function removeListingItem($day, Listing $listing)
    {
        $this->listingItemsManager->removeListingItem($day, $listing);
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return mixed
     * @throws ShiftItemDownException
     * @throws \Exception
     */
    public function copyListingItemDown(
        $day,
        Listing $listing
    ) {
        return $this->listingItemsManager
                    ->copyListingItem(
                        $day,
                        $listing,
                        ListingItemsWriter::WRITE_DOWN
                    );
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return mixed
     * @throws ShiftItemUpException
     * @throws \Exception
     */
    public function copyListingItemUp(
        $day,
        Listing $listing
    ) {
        return $this->listingItemsManager
                    ->copyListingItem(
                        $day,
                        $listing,
                        ListingItemsWriter::WRITE_UP
                    );
    }

    /**
     * @param array $listingItems
     * @return array Array of ListingItemDecorators
     */
    public function convert2DisplayableItems(array $listingItems)
    {
        return $this->itemsService->convert2DisplayableItems($listingItems);
    }

    /**
     * @param Listing $listing
     * @return ListingItemDecorator[]
     */
    public function generateEntireTable(
        Listing $listing
    ) {
        $listingItems = $this->listingItemsReader->findListingItems($listing);

        $collectionOfDecorators = $this->convert2DisplayableItems(
            $listingItems
        );

        return $this->itemsService->generateEntireTable(
            $collectionOfDecorators,
            $listing->getPeriod()
        );
    }

}