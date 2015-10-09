<?php

namespace App\Model\Facades;

use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
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
use App\Model\Services\ItemsService;
use Nette\Object;

class ItemsFacade extends Object
{
    /** @var ListingItemsManager  */
    private $listingItemsManager;

    /** @var ListingItemsWriter  */
    private $listingItemsWriter;

    /** @var ListingItemsReader  */
    private $listingItemsReader;

    /** @var ItemsService  */
    private $itemsService;

    public function __construct(
        ListingItemsManager $listingItemManager,
        ListingItemsWriter $listingItemsWriter,
        ListingItemsReader $listingItemReader,
        ItemsService $itemService
    ) {
        $this->listingItemsManager = $listingItemManager;
        $this->listingItemsWriter = $listingItemsWriter;
        $this->listingItemsReader = $listingItemReader;

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
        $item = $this->listingItemsManager
                     ->prepareListingItemByFormsData($newValues, $listingItem);

        return $this->listingItemsWriter->saveListingItem($item);
    }

    /**
     * @param $day
     * @param Listing $listing
     * @return ListingItem|null
     */
    public function getByDay($day, Listing $listing)
    {
        return $this->listingItemsReader->getByDay($day, $listing);
    }

    /**
     * @param int $day
     * @param Listing $listing
     */
    public function removeListingItem($day, Listing $listing)
    {
        $this->listingItemsWriter->removeListingItem($day, $listing);
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return mixed
     * @throws ShiftItemDownException
     */
    public function copyListingItemDown(
        $day,
        Listing $listing
    ) {
        $currentItem = $this->listingItemsReader->getByDay($day, $listing);

        return $this->listingItemsWriter
                    ->copyListingItem(
                        $currentItem,
                        ListingItemsWriter::WRITE_DOWN
                    );
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return mixed
     * @throws ShiftItemUpException
     */
    public function copyListingItemUp(
        $day,
        Listing $listing
    ) {
        $currentItem = $this->listingItemsReader->getByDay($day, $listing);

        return $this->listingItemsWriter
                    ->copyListingItem(
                        $currentItem,
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