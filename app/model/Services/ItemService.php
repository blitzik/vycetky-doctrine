<?php

namespace App\Model\Services;

use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\IDisplayableItem;
use App\Model\Domain\FillingItem;
use App\Model\Domain\Entities;
use App\Model\Time\TimeUtils;
use Nette\Object;

class ItemService extends Object
{

    /**
     * @param Entities\ListingItem[] $listingItems
     * @return Entities\ListingItem[] Array of detached entities
     */
    public function createItemsCopies(array $listingItems)
    {
        $collection = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof Entities\ListingItem or
                $listingItem->isDetached()) {
                throw new InvalidArgumentException(
                    'Only attached instances of ' .Entities\ListingItem::class. ' can pass.'
                );
            }
            $collection[] = clone $listingItem;
        }

        return $collection;
    }

    /**
     * @param Entities\ListingItem[] $listingItems
     * @param Entities\Listing $listing
     * @return array
     */
    public function setListingForGivenItems(
        array $listingItems,
        Entities\Listing $listing
    ) {
        if ($listing->isDetached())
            throw new InvalidArgumentException(
                'Only attached(not detached) '.Entities\Listing::class.' entity can pass!'
            );

        $newItemsCollection = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof Entities\ListingItem) {
                throw new InvalidArgumentException(
                    'Only instances of ' .Entities\ListingItem::class. ' can pass.'
                );
            }
            $listingItem->setListing($listing);

            $newItemsCollection[] = $listingItem;
        }

        return $newItemsCollection;
    }

    /**
     * @param array $listingItems
     * @return array Array of ListingItemDecorators
     */
    public function createDecoratorsCollection(
        array $listingItems
    ) {
        $collection = [];
        foreach ($listingItems as $listingItem) {
            if ($listingItem instanceof FillingItem) {
                $collection[$listingItem->day->format('j')] = $listingItem;

            } else if ($listingItem instanceof Entities\ListingItem) {
                $collection[$listingItem->day] = new ListingItemDecorator($listingItem);
            } else {
                throw new InvalidArgumentException(
                    'Only instances of '.Entities\ListingItem::class.' or '.FillingItem::class.' can be processed'
                );
            }
        }

        return $collection;
    }

    /**
     * If there are 2 items in one particular day, the item from
     * base listing is always the first one
     *
     * @param array $baseItems
     * @param array $items
     * @return array
     */
    public function mergeListingItems(
        array $baseItems,
        array $items
    ) {
        $baseListingItems = array();
        foreach ($baseItems as $listingItem) {
            $this->checkListingItemValidity($listingItem);
            $baseListingItems[$listingItem->day] = $listingItem;
        }

        $listingItems = array();
        foreach ($items as $listingItem) {
            $this->checkListingItemValidity($listingItem);
            $listingItems[$listingItem->day] = $listingItem;
        }

        $resultCollection = array();
        for ($day = 1; $day <= 31; $day++) {
            if (isset($baseListingItems[$day]) and isset($listingItems[$day])) {
                if ($baseListingItems[$day]->compare($listingItems[$day], ['listingItemID', 'listingID'])) {
                    $resultCollection[$day][] = $baseListingItems[$day];
                } else {

                    $resultCollection[$day][] = $baseListingItems[$day];
                    $resultCollection[$day][] = $listingItems[$day];
                }
            } else {

                if (isset($baseListingItems[$day])) {
                    $resultCollection[$day][] = $baseListingItems[$day];
                    continue;
                }

                if (isset($listingItems[$day])) {
                    $resultCollection[$day][] = $listingItems[$day];
                }
            }
        }

        return $resultCollection;
    }

    /**
     * @param ListingItemDecorator[] $listingItemsDecorators
     * @param \DateTime $period
     * @return array
     */
    public function generateListingItemDecoratorsForEntireTable(
        array $listingItemsDecorators,
        \DateTime $period
    ) {
        $year = $period->format('Y');
        $month = $period->format('n');
        $daysInMonth = TimeUtils::getNumberOfDaysInMonth($year, $month);

        $list = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            if (array_key_exists($day, $listingItemsDecorators)) {
                if (!$listingItemsDecorators[$day] instanceof IDisplayableItem) {
                    throw new InvalidArgumentException(
                        'Only instances of '.ListingItemDecorator::class.' can pass.'
                    );
                }
                $list[$day] = $listingItemsDecorators[$day];
            } else {

                $list[$day] = new FillingItem(
                    TimeUtils::getDateTimeFromParameters($year, $month, $day)
                );
            }
        }

        return $list;
    }

    /**
     * @param Entities\Listing $baseListing
     * @param Entities\Listing $listingToMerge
     * @param array $selectedCollisionItems
     * @return array
     * @throws NoCollisionListingItemSelectedException
     */
    public function getMergedListOfItems(
        Entities\Listing $baseListing,
        Entities\Listing $listingToMerge,
        array $selectedCollisionItems = []
    ) {
        $selectedCollisionItems = array_flip($selectedCollisionItems);

        $mergedItems = $this->mergeListingItems(
                                $baseListing->listingItems,
                                $listingToMerge->listingItems
                            );

        $numberOfCheckedCollisionItems = null;
        $items = array();
        foreach ($mergedItems as $day => $listingItems) {
            $numberOfCheckedCollisionItems = 0;

            foreach ($listingItems as $item) {
                if (count($listingItems) > 1) {
                    if (array_key_exists($item->listingItemID, $selectedCollisionItems)) {
                        // it will always make clone of the first found item (from base listing)
                        $items[] = clone $item;
                        break;
                    }
                    $numberOfCheckedCollisionItems++;
                    if ($numberOfCheckedCollisionItems >= 2) {
                        // One day can have max. 2 colliding items
                        // and if none of them is selected, throw exception
                        throw new NoCollisionListingItemSelectedException;
                    }

                } else {

                    $items[] = clone $item;
                }
            }
        }

        return $items;
    }

    /**
     * Checks whether given $listingItem is Instance of ListingItem and is attached
     * (not Detached)
     * @param $listingItem
     */
    public function checkListingItemValidity($listingItem)
    {
        if (!$listingItem instanceof Entities\ListingItem or
             $listingItem->isDetached()) {
            throw new InvalidArgumentException(
                'Only Attached instances of '.Entities\ListingItem::class.' can pass.'
            );
        }
    }
}