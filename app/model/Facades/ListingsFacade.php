<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\ListingItem;
use App\Model\Query\ListingsQuery;
use App\Model\Services\Managers\ListingsManager;
use App\Model\Services\Readers\ListingsReader;
use Doctrine\ORM\ORMException;
use Exceptions\Runtime\ListingNotFoundException;
use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\Listing;
use App\Model\Services\ItemsService;
use App\Model\Domain\FillingItem;
use Nette\Object;
use Nette\Utils\Validators;
use Tracy\Debugger;

class ListingsFacade extends Object
{
    /**
     * @var ListingsManager
     */
    private $listingsManager;

    /**
     * @var ItemsService
     */
    private $itemsService;

    /**
     * @var ListingsReader
     */
    private $listingsReader;

    /**
     * @var ItemsFacade
     */
    private $itemsFacade;

    /**
     * @var \Nette\Security\User
     */
    private $user;

    public function __construct(
        ListingsManager $listingsManager,
        ListingsReader $listingsReader,
        ItemsService $itemService,
        ItemsFacade $itemFacade,
        \Nette\Security\User $user
    ) {
        $this->listingsManager = $listingsManager;
        $this->listingsReader = $listingsReader;

        $this->itemsService = $itemService;
        $this->itemsFacade = $itemFacade;
        $this->user = $user;
    }

    /**
     * @param Listing $listing
     * @return Listing
     */
    public function saveListing(Listing $listing)
    {
        return $this->listingsManager->saveListing($listing);
    }

    /**
     * @param ListingsQuery $listingsQuery
     * @return mixed
     * @throws ListingNotFoundException
     */
    public function fetchListing(ListingsQuery $listingsQuery)
    {
        return $this->listingsReader->fetchListing($listingsQuery);
    }

    /**
     * @param ListingsQuery $listingsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchListings(ListingsQuery $listingsQuery)
    {
        return $this->listingsReader->fetchListings($listingsQuery);
    }

    /**
     * @param Listing $listing
     */
    public function removeListing(Listing $listing)
    {
        $this->listingsManager->removeListing($listing);
    }

    /**
     * @param Listing $listing
     * @param bool $withItems
     * @param array|null $valuesForNewListing
     * @return Listing
     * @throws ORMException
     */
    public function establishListingCopy(
        Listing $listing,
        $withItems = true,
        array $valuesForNewListing = null
    ) {
        return $this->listingsManager
                    ->establishListingCopy(
                        $listing,
                        $withItems,
                        $valuesForNewListing
                    );
    }

    /**
     * @param Listing $listing
     * @param WorkedHours $newWorkedHours
     * @param array $daysToChange
     * @return ListingItem[] Items that have been updated
     */
    public function changeItems(
        Listing $listing,
        WorkedHours $newWorkedHours,
        array $daysToChange
    ) {
        return $this->listingsManager
                    ->changeWorkedHours($listing, $newWorkedHours, $daysToChange);
    }

    /**
     * @param Listing $listing
     * @param WorkedHours $newWorkedHours
     * @param array $daysToChange
     * @return Listing
     */
    public function baseListingOn(
        Listing $listing,
        WorkedHours $newWorkedHours,
        array $daysToChange
    ) {
        return $this->listingsManager
                    ->baseListingOn($listing, $newWorkedHours, $daysToChange);
    }

    /**
     * @param Listing $listing
     * @param $description
     * @param array $recipients
     * @param array $ignoredItemsIDs
     * @return Listing[]
     * @throws \DibiException
     */
    public function shareListing(
        Listing $listing,
        $description,
        array $recipients,
        array $ignoredItemsIDs = null
    ) {
        $this->checkListingValidity($listing);
        Validators::assert($description, 'unicode');

        $listingItems = $listing->listingItems;

        if (isset($ignoredItemsIDs)) {
            $ignoredItemsIDs = array_flip($ignoredItemsIDs);
            foreach ($listingItems as $key => $item) {
                if (array_key_exists($item->listingItemID, $ignoredItemsIDs)) {
                    unset($listingItems[$key]);
                }
            }
        }

        $newListings = [];
        foreach ($recipients as $recipientID) {
            $newListing = clone $listing;
            $newListing->user = $recipientID;
            $newListing->description = $description;
            $newListing->hourlyWage = null;

            $newListings[] = $newListing;
        }

        try {
            $this->transaction->begin();

            $this->listingRepository->saveListings($newListings);

            $items = [];
            foreach ($newListings as $listing) {
                $newItemsForListing = $this->itemsService
                                           ->createItemsCopies($listingItems);
                $newItemsForListing = $this->itemsService
                                           ->setListingForGivenItems(
                                               $newItemsForListing,
                                               $listing
                                           );

                $items = array_merge($items, $newItemsForListing);
                if (count($items) > 120) {
                    $this->listingItemRepository->saveListingItems($items);
                    unset($items);

                    $items = [];
                }
            }
            // save the rest items
            if (!empty($items)) {
                $this->listingItemRepository->saveListingItems($items);
            }

            $this->transaction->commit();

            return $newListings;

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $baseListing
     * @param Listing $listing
     * @return array
     */
    public function getMergedListingsItemsForEntireTable(
        Listing $baseListing,
        Listing $listing
    ) {
        $this->checkListingValidity($baseListing);
        $this->checkListingValidity($listing);

        if (!$this->haveListingsSamePeriod($baseListing, $listing)) {
            throw new InvalidArgumentException(
                'Given Listings must have same Period(Year and Month).'
            );
        }

        $items = $this->itemsService
                      ->mergeListingItems(
                          $baseListing->listingItems,
                          $listing->listingItems
                      );

        $days = $baseListing->getNumberOfDaysInMonth();


        $result = array();
        for ($day = 1; $day <= $days; $day++) {
            if (!array_key_exists($day, $items)) {
                $result[$day][] = new FillingItem(
                    new \DateTime(
                        $baseListing->year.'-'.$baseListing->month.'-'.$day
                    )
                );
            } else {
                foreach ($items[$day] as $key => $item) {
                    $itemDec = new ListingItemDecorator($item);
                    $itemDec->setAsItemFromBaseListing(true);

                    if ($key != 0) {
                        $itemDec->setAsItemFromBaseListing(false);
                    }

                    $result[$day][] = $itemDec;
                }
            }
        }

        return $result;
    }

    /**
     * @param Listing $baseListing
     * @param Listing $listingToMerge
     * @param array $selectedCollisionItems
     * @param \App\Model\Entities\User|int|null $user
     * @return Listing
     * @throws NoCollisionListingItemSelectedException
     * @throws \DibiException
     */
    public function mergeListings(
        Listing $baseListing,
        Listing $listingToMerge,
        array $selectedCollisionItems = [],
        $user = null
    ) {
        $this->checkListingValidity($baseListing);
        $this->checkListingValidity($listingToMerge);

        if (!$this->haveListingsSamePeriod($baseListing, $listingToMerge)) {
            throw new InvalidArgumentException(
                'Given Listings must have same Period(Year and Month).'
            );
        }

        $userID = $this->getIdOfSignedInUserOnNull($user);

        $items = $this->itemsService->getMergedListOfItems(
            $baseListing,
            $listingToMerge,
            $selectedCollisionItems
        );

        try {
            $this->transaction->begin();

            $newListing = new Listing(
                $baseListing->year,
                $baseListing->month,
                $userID
            );

            $this->saveListing($newListing);

            $this->itemsService->setListingForGivenItems($items, $newListing);

            $this->listingItemRepository->saveListingItems($items);

            $this->transaction->commit();

            return $newListing;

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param array $listingItems
     */
    private function persistListingItems(array $listingItems)
    {
        $this->listingItemRepository->saveListingItems($listingItems);
    }

    /**
     * @param Listing $base
     * @param Listing $second
     * @return bool
     */
    public function haveListingsSamePeriod(Listing $base, Listing $second)
    {
        if ($base->year === $second->year and $base->month === $second->month) {
            return true;
        }

        return false;
    }

    /**
     * @param Listing $listing
     * @throws InvalidArgumentException
     */
    private function checkListingValidity(Listing $listing)
    {
        if ($listing->isDetached()) {
            throw new InvalidArgumentException(
                'Argument $listing must be attached instance of ' . Listing::class
            );
        }
    }

}