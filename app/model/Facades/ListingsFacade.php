<?php

namespace App\Model\Facades;

use App\Model\Domain\Entities\ListingItem;
use App\Model\ResultObjects\ListingResult;
use App\Model\Services\Managers\ListingsManager;
use App\Model\Services\Readers\ListingsReader;
use App\Model\Services\Readers\UsersReader;
use App\Model\Services\Writers\ListingsWriter;
use App\Model\Subscribers\Results\ResultObject;
use Doctrine\ORM\ORMException;
use Exceptions\Runtime\ListingNotFoundException;
use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\Listing;
use App\Model\Services\ItemsService;
use App\Model\Domain\FillingItem;
use Exceptions\Runtime\RecipientsNotFoundException;
use Kdyby\Doctrine\QueryObject;
use Nette\Object;
use Tracy\Debugger;

class ListingsFacade extends Object
{
    /** @var array  */
    public $onListingSharing = [];

    /** @var ListingsManager  */
    private $listingsManager;

    /** @var ItemsService  */
    private $itemsService;

    /** @var ListingsReader  */
    private $listingsReader;

    /** @var ListingsWriter  */
    private $listingsWriter;

    /** @var ItemsFacade  */
    private $itemsFacade;

    /** @var UsersReader  */
    private $usersReader;

    /**
     * @var \Nette\Security\User
     */
    private $user;

    public function __construct(
        ListingsManager $listingsManager,
        ListingsReader $listingsReader,
        ListingsWriter $listingsWriter,
        ItemsService $itemService,
        UsersReader $usersReader,
        ItemsFacade $itemFacade,
        \Nette\Security\User $user
    ) {
        $this->listingsManager = $listingsManager;
        $this->listingsReader = $listingsReader;
        $this->listingsWriter = $listingsWriter;

        $this->itemsService = $itemService;
        $this->usersReader = $usersReader;
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
     * @param QueryObject $listingsQuery
     * @return Listing|null
     * @throws ListingNotFoundException
     */
    public function fetchListing(QueryObject $listingsQuery)
    {
        return $this->listingsReader->fetchListing($listingsQuery);
    }

    /**
     * @param QueryObject $listingsQuery
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchListings(QueryObject $listingsQuery)
    {
        return $this->listingsReader->fetchListings($listingsQuery);
    }

    /**
     * @param int $id
     * @param bool $withWorkedTime
     * @return ListingResult
     */
    public function getListingByID($id, $withWorkedTime = false)
    {
        $result = $this->listingsReader->getByID($id, $withWorkedTime);

        return new ListingResult($result);
    }

    /**
     * @param $listingID
     * @return array
     */
    public function getWorkedDaysAndTime($listingID)
    {
        return $this->listingsReader->getWorkedDaysAndTime($listingID);
    }

    /**
     * @param Listing $listing
     */
    public function removeListing(Listing $listing)
    {
        $this->listingsWriter->removeListing($listing);
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
     * @param int $recipientID
     * @param $description
     * @param array|null $ignoredListingDays
     * @return ResultObject
     * @throws RecipientsNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function shareListing(
        Listing $listing,
        $recipientID,
        $description,
        array $ignoredListingDays = []
    ) {
        $recipient = $this->usersReader->findUsersByIDs([$recipientID]);
        if (empty($recipient)) {
            throw new RecipientsNotFoundException;
        }

        $newListing =  $this->listingsManager
                            ->shareListing(
                                $listing,
                                $recipient[0],
                                $description,
                                $ignoredListingDays
                            );

        $resultObject = new ResultObject($newListing);
        $this->onListingSharing($newListing, $listing->getUser(), $resultObject);

        return $resultObject;
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

}