<?php

namespace App\Model\Facades;

use App\Model\Query\ListingsQuery;
use Exceptions\Runtime\ListingNotFoundException;
use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\ListingItemNotFoundException;
use App\Model\Repositories\ListingItemRepository;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Repositories\ListingRepository;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Listing;
use App\Model\Services\ItemService;
use App\Model\Domain\FillingItem;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Utils\Validators;
use Nette\Security\User;
use Tracy\Debugger;

class ListingFacade extends BaseFacade
{
    /**
     * @var ListingItemRepository
     */
    private $listingItemRepository;

    /**
     * @var EntityRepository
     */
    private $listingRepository;

    /**
     * @var \Transaction
     */
    private $transaction;

    /**
     * @var ItemService
     */
    private $itemService;

    /**
     * @var ItemFacade
     */
    private $itemFacade;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        ListingItemRepository $listingItemRepository,
        EntityManager $entityManager,
        \Transaction $transaction,
        ItemService $itemService,
        ItemFacade $itemFacade,
        User $user
    ) {
        parent::__construct($user);
        $this->em = $entityManager;
        $this->listingRepository = $this->em->getRepository(Listing::class);

        $this->listingItemRepository = $listingItemRepository;
        $this->transaction = $transaction;
        $this->itemService = $itemService;
        $this->itemFacade = $itemFacade;
    }

    /**
     * @param Listing $listing
     * @return Listing
     */
    public function saveListing(Listing $listing)
    {
        $this->em->persist($listing)->flush();
        return $listing;
    }

    /**
     * @param ListingsQuery $listingsQuery
     * @return mixed
     * @throws ListingNotFoundException
     */
    public function fetchListing(ListingsQuery $listingsQuery)
    {
        $listingData = $this->listingRepository->fetchOne($listingsQuery);
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
        return $this->listingRepository->fetch($listingsQuery);
    }

    /**
     * @param Listing $listing
     * @return mixed
     */
    public function removeListing(Listing $listing)
    {
        return $this->listingRepository->delete($listing);
    }

    /**
     * @param int $id
     * @param User|int|null $user
     * @return Listing
     */
    public function getEntireListingByID($id, $user = null)
    {
        Validators::assert($id, 'numericint');
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->listingRepository
                    ->getEntireListingByID($id, $userID);
    }

    /**
     * @param int $id
     * @return Listing
     * @throws ListingItemNotFoundException
     */
    public function getListingByID($id)
    {
        return $this->listingRepository->getListingByID($id);
    }

    /**
     * @param int $year
     * @param int $month
     * @param User|int|null $user
     * @return Listing[]
     */
    public function findListingsByPeriod($year, $month = null, $user = null)
    {
        Validators::assert($year, 'numericint');
        Validators::assert($month, 'numericint|null');
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->listingRepository
                    ->findUserListingsByPeriod(
                        $userID,
                        $year,
                        $month
                    );
    }

    /**
     * @param int $year
     * @param int $month
     * @param User|int|null $user
     * @return array
     */
    public function findPartialListingsDataForSelect(
        $year,
        $month,
        $user = null
    ) {
        Validators::assert($year, 'numericint');
        Validators::assert($month, 'numericint');
        $userID = $this->getIdOfSignedInUserOnNull($user);

        $listings =  $this->listingRepository
                          ->findPartialListings(
                              $userID,
                              $year,
                              $month
                          );

        $result = array();
        foreach ($listings as $listing) {
            $result[$listing->listingID] = ' [#'.$listing->listingID .'] ' . $listing->entireDescription();
        }

        return $result;
    }

    /**
     * @param Listing $listing
     * @param array|null $listingItems
     * @return \App\Model\Entities\ListingItem[]
     */
    private function getItemsCopies(Listing $listing, array $listingItems = null)
    {
        $this->checkListingValidity($listing);

        $items = null;
        if (isset($listingItems)) {
            $items = $this->itemService->setListingForGivenItems($listingItems, $listing);
        } else {
            $items = $listing->listingItems;
        }

        $itemsCopies = $this->itemService->createItemsCopies($items);

        return $itemsCopies;
    }

    /**
     * @param Listing $listing
     * @param bool $withItems
     * @param \App\Model\Domain\Entities\User|null $user
     * @return Listing
     * @throws \DibiException
     */
    public function establishListingCopy(
        Listing $listing,
        $withItems = true,
        \App\Model\Domain\Entities\User $user = null
    ) {
        Validators::assert($withItems, 'boolean');

        $newListing = clone $listing;
        if ($user !== null) {
            $newListing->setUser($user);
        }

        try {
            $this->transaction->begin(); // todo dodelat tuto netodu

            $this->listingRepository->persist($newListing);

            if ($withItems === true and count($listing->listingItems) > 0) {
                $newItems = $this->getItemsCopies($newListing, $listing->listingItems);
                $this->persistListingItems($newItems);
            }

            $this->transaction->commit();

            return $newListing;

        } catch (\DibiException $e) {

            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $listing
     * @param WorkedHours $workedHours
     * @param bool $createNewListing
     * @param array $selectedItemsIDsToChange
     * @return array
     * @throws \DibiException
     * @throws NegativeResultOfTimeCalcException
     */
    public function changeItemsInListing(
        Listing $listing,
        WorkedHours $workedHours,
        $createNewListing = true,
        array $selectedItemsIDsToChange
    ) {
        $this->checkListingValidity($listing);
        Validators::assert($createNewListing, 'boolean');

        if (empty($selectedItemsIDsToChange)) {
            throw new InvalidArgumentException(
                'Argument $selectedItemsIDsToChange must not be empty array!'
            );
        }

        $listingItems = $listing->listingItems;

        $itemsToChange = [];
        $itemsToCopy = []; // items that won't change

        $selectedItemsToChange = array_flip($selectedItemsIDsToChange);

        foreach ($listingItems as $listingItem) {
            if (array_key_exists($listingItem->listingItemID, $selectedItemsToChange)) {
                $itemsToChange[] = $listingItem;
            } else {
                $itemsToCopy[] = $listingItem;
            }
        }

        try {
            $this->transaction->begin();

            // amount of Items is same all the time
            $numberOfItemsToChange = count($itemsToChange);

            if ($createNewListing === true) {
                $listing = $this->establishListingCopy($listing, false);

                if (count($itemsToCopy) > 0) {
                    $itemsToCopy = $this->getItemsCopies($listing, $itemsToCopy);
                }

                if ($numberOfItemsToChange > 0) {;
                    $itemsToChange = $this->getItemsCopies($listing, $itemsToChange);
                }
            }

            if ($numberOfItemsToChange > 0) {
                $workedHours = $this->itemFacade
                                    ->setupWorkedHoursEntity($workedHours);

                $whInSecs = $workedHours->otherHours->toSeconds();
                foreach ($itemsToChange as $item) {
                    $descOtherHours = null;
                    if (isset($item->descOtherHours) and $whInSecs > 0) {
                            $descOtherHours = $item->descOtherHours;
                    }
                    $item->setWorkedTime($workedHours, $descOtherHours);
                }
            }

            if ($createNewListing === true) {
                $allItems = array_merge($itemsToChange, $itemsToCopy);
                $this->listingItemRepository->saveListingItems($allItems);

            } else {

                $this->listingItemRepository
                     ->updateListingItemsWorkedHours(
                         $itemsToChange,
                         $workedHours
                     );
            }

            $this->transaction->commit();

            return ['listing'      => $listing,
                    'changedItems' => $itemsToChange];

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
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
                $newItemsForListing = $this->itemService
                                           ->createItemsCopies($listingItems);
                $newItemsForListing = $this->itemService
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

        $items = $this->itemService
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

        $items = $this->itemService->getMergedListOfItems(
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

            $this->itemService->setListingForGivenItems($items, $newListing);

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