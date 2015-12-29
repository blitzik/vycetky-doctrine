<?php

namespace App\Model\Services;

use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\IDisplayableItem;
use App\Model\Domain\FillingItem;
use App\Model\Domain\Entities;
use App\Model\Time\TimeUtils;
use Nette\Object;

class ItemsService extends Object
{
    /**
     * @param Entities\ListingItem[] $listingItems
     * @return IDisplayableItem[]
     */
    public function prepareDisplayableItemsCollection(
        array $listingItems
    ) {
        $collection = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof IDisplayableItem) {
                throw new InvalidArgumentException(
                    'Only instances of '.Entities\ListingItem::class.' or '.FillingItem::class.' can be processed'
                );
            }

            $collection[$listingItem->getDate()->format('j')] = $listingItem;
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
            if (!isset($baseListingItems[$day]) and !isset($listingItems[$day])) {
                continue;
            }

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
     * @param IDisplayableItem[] $displayableItems
     * @param \DateTime $period
     * @return array
     */
    public function generateEntireTable(
        array $displayableItems,
        \DateTime $period
    ) {
        $year = $period->format('Y');
        $month = $period->format('n');
        $daysInMonth = TimeUtils::getNumberOfDaysInMonth($year, $month);

        $list = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            if (array_key_exists($day, $displayableItems)) {
                if (!$displayableItems[$day] instanceof IDisplayableItem) {
                    throw new InvalidArgumentException(
                        'Only instances of '.IDisplayableItem::class.' can pass.'
                    );
                }
                $list[$day] = $displayableItems[$day];
            } else {

                $list[$day] = new FillingItem(
                    TimeUtils::getDateTimeFromParameters($year, $month, $day)
                );
            }
        }

        return $list;
    }


    /**
     * @param array $baseListingItems
     * @param array $listingToMergeItems
     * @param array $selectedCollisionItems
     * @return array
     * @throws NoCollisionListingItemSelectedException
     */
    public function getMergedListOfItems(
        array $baseListingItems,
        array $listingToMergeItems,
        array $selectedCollisionItems = []
    ) {
        $selectedCollisionItems = array_flip($selectedCollisionItems);

        $mergedItems = $this->mergeListingItems(
                                $baseListingItems,
                                $listingToMergeItems
                            );

        $numberOfCheckedCollisionItems = null;
        $items = array();
        foreach ($mergedItems as $day => $listingItems) {
            $numberOfCheckedCollisionItems = 0;

            foreach ($listingItems as $item) {
                if (count($listingItems) > 1) {
                    if (array_key_exists($item->getId(), $selectedCollisionItems)) {
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
     * @param $listingItem
     */
    public function checkListingItemValidity($listingItem)
    {
        if (!$listingItem instanceof Entities\ListingItem) {
            throw new InvalidArgumentException(
                'Only Attached instances of '.Entities\ListingItem::class.' can pass.'
            );
        }
    }
}