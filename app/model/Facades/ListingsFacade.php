<?php

namespace App\Model\Facades;

use App\Model\Domain\MergeableListingItem;
use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Runtime\RecipientsNotFoundException;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Managers\ListingsManager;
use Exceptions\Runtime\ListingNotFoundException;
use App\Model\Subscribers\Results\EntityResultObject;
use App\Model\Services\Readers\ListingsReader;
use App\Model\Services\Writers\ListingsWriter;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Services\Readers\UsersReader;
use App\Model\ResultObjects\ListingResult;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\Listing;
use App\Model\Services\ItemsService;
use App\Model\Domain\Entities\User;
use App\Model\Domain\FillingItem;
use Kdyby\Doctrine\QueryObject;
use Doctrine\ORM\ORMException;
use Nette\Utils\Arrays;
use Nette\Object;

class ListingsFacade extends Object
{
    /** @var array */
    public $onListingSharing = [];

    /** @var array */
    public $onListingChange = [];

    /** @var ListingItemsReader  */
    private $listingItemsReader;

    /** @var ListingsManager  */
    private $listingsManager;

    /** @var ListingsReader  */
    private $listingsReader;

    /** @var ListingsWriter  */
    private $listingsWriter;

    /** @var ItemsService  */
    private $itemsService;

    /** @var ItemsFacade  */
    private $itemsFacade;

    /** @var UsersReader  */
    private $usersReader;

    public function __construct(
        ListingItemsReader $listingItemsReader,
        ListingsManager $listingsManager,
        ListingsReader $listingsReader,
        ListingsWriter $listingsWriter,
        ItemsService $itemService,
        UsersReader $usersReader,
        ItemsFacade $itemFacade
    ) {
        $this->listingItemsReader = $listingItemsReader;
        $this->listingsManager = $listingsManager;
        $this->listingsReader = $listingsReader;
        $this->listingsWriter = $listingsWriter;

        $this->itemsService = $itemService;
        $this->usersReader = $usersReader;
        $this->itemsFacade = $itemFacade;
    }

    /**
     * @param Listing $listing
     * @return Listing
     */
    public function saveListing(Listing $listing)
    {
        $this->onListingChange($listing);
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
     * @return ListingResult|null
     */
    public function getListingByID($id, $withWorkedTime = false)
    {
        $result = $this->listingsReader->getByID($id, $withWorkedTime);
        if ($result === null) {
            return null;
        }

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
     * @param User $user
     * @param int $year
     * @param int $month
     * @return array Array listingID => description
     */
    public function findListingsToMerge(User $user, $year, $month)
    {
        $listings =  $this->listingsReader
                          ->findListingsToMerge($user, $year, $month);

        return Arrays::associate($listings, 'id=description');
    }

    /**
     * @param Listing $listing
     */
    public function removeListing(Listing $listing)
    {
        $this->onListingChange($listing);
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
        $this->onListingChange($listing);

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
     * @return EntityResultObject
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

        $resultObject = new EntityResultObject($newListing);
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
        if (!$this->haveListingsSamePeriod($baseListing, $listing)) {
            throw new InvalidArgumentException(
                'Given Listings must have same Period(Year and Month).'
            );
        }

        $items = $this->itemsService
                      ->mergeListingItems(
                          $this->listingItemsReader->findListingItems($baseListing->getId()),
                          $this->listingItemsReader->findListingItems($listing->getId())
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
                    $itemDec = new MergeableListingItem($item);
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
     * @param User $ownerOfOutputListing
     * @return Listing
     * @throws NoCollisionListingItemSelectedException
     */
    public function mergeListings(
        Listing $baseListing,
        Listing $listingToMerge,
        array $selectedCollisionItems = [],
        User $ownerOfOutputListing
    ) {
        return $this->listingsManager
                    ->mergeListings(
                        $baseListing,
                        $listingToMerge,
                        $selectedCollisionItems,
                        $ownerOfOutputListing
                    );
    }

    /**
     * @param User|null $user
     * @return array
     */
    public function getListingsYears(User $user = null)
    {
        return Arrays::associate($this->listingsReader->getListingsYears($user), 'year');
    }

    /**
     * @param Listing $listing1
     * @param Listing $listing2
     * @return bool
     */
    public function haveListingsSamePeriod(Listing $listing1, Listing $listing2)
    {
        return $this->listingsManager->haveListingsSamePeriod($listing1, $listing2);
    }

}