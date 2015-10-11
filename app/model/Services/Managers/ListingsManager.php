<?php

namespace App\Model\Services\Managers;

use App\Model\Domain\Entities\User;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Services\ItemsService;
use App\Model\Services\Providers\WorkedHoursProvider;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Services\Readers\UsersReader;
use App\Model\Services\Writers\ListingsWriter;
use Doctrine\DBAL\DBALException;
use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Runtime\RuntimeException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\Validators;
use Tracy\Debugger;

class ListingsManager extends Object
{
    /** @var WorkedHoursProvider  */
    private $workedHoursProvider;

    /** @var ListingItemsReader  */
    private $listingItemsReader;

    /** @var ListingsWriter  */
    private $listingsWriter;

    /** @var ItemsService  */
    private $itemsService;

    /** @var UsersReader  */
    private $usersReader;

    /** @var EntityManager  */
    private $em;


    public function __construct(
        WorkedHoursProvider $workedHoursProvider,
        ListingItemsReader $listingItemsReader,
        ListingsWriter $listingsWriter,
        EntityManager $entityManager,
        ItemsService $itemsService,
        UsersReader $usersReader
    ) {
        $this->workedHoursProvider = $workedHoursProvider;
        $this->listingItemsReader = $listingItemsReader;
        $this->listingsWriter = $listingsWriter;
        $this->itemsService = $itemsService;
        $this->usersReader = $usersReader;
        $this->em = $entityManager;
    }

    /**
     * @param Listing $listing
     * @return Listing
     */
    public function saveListing(Listing $listing)
    {
        return $this->listingsWriter->saveListing($listing);
    }

    /**
     * @param Listing $listing
     * @param bool $withItems
     * @param array|null $valuesForNewListing
     * @return Listing
     * @throws DBALException
     */
    public function establishListingCopy(
        Listing $listing,
        $withItems = true,
        array $valuesForNewListing = null
    ) {
        Validators::assert($withItems, 'boolean');

        $newListing = clone $listing;
        if (isset($valuesForNewListing)) {
            $newListing->setDescription($valuesForNewListing['description']);
            $newListing->setHourlyWage($valuesForNewListing['hourlyWage']);
        }
        $this->em->persist($newListing);

        if ($withItems === true) {
            $copies = $this->getItemsCopies($listing);
            if (!empty($copies)) {
                foreach ($copies as $copy) {
                    $copy->setListing($newListing);
                    $this->em->persist($copy);
                }
            }
        }

        try {
            $this->em->flush();
            $this->em->clear();

        } catch (DBALException $e) {
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }

        return $newListing;
    }

    /**
     * @param Listing $listing
     * @param array|null $days
     * @return array
     */
    private function getItemsCopies(Listing $listing, array $days = null)
    {
        $items = $this->listingItemsReader
                      ->findListingItems($listing->getId(), $days);

        $copies = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $copies[$item->day] = clone $item;
            }
        }

        return $copies;
    }

    /**
     * @param Listing $listing
     * @param WorkedHours $newWorkedHours
     * @param array $daysToChange
     * @return Listing
     * @throws DBALException
     */
    public function baseListingOn(
        Listing $listing,
        WorkedHours $newWorkedHours,
        array $daysToChange
    ) {
        $newListing = clone $listing;
        $this->em->persist($newListing);

        try {
            $workedHours = $this->workedHoursProvider
                                ->setupWorkedHoursEntity($newWorkedHours);

            $itemsCopies = $this->getItemsCopies($listing);

            $daysToChange = array_flip($daysToChange);
            foreach ($itemsCopies as $itemCopy) {
                if (array_key_exists($itemCopy->day, $daysToChange)) {
                    /** @var ListingItem $itemCopy */
                    $itemCopy->setWorkedTime($workedHours);
                }
                $itemCopy->setListing($newListing);
                $this->em->persist($itemCopy);
            }

            $this->em->flush();

            return $newListing;

        } catch (DBALException $e) {
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $listing
     * @param WorkedHours $newWorkedHours
     * @param array $daysToChange
     * @return ListingItem[]
     */
    public function changeWorkedHours(
        Listing $listing,
        WorkedHours $newWorkedHours,
        array $daysToChange
    ) {
        $workedHours = $this->workedHoursProvider
                            ->setupWorkedHoursEntity($newWorkedHours);

        $this->em->createQuery(
            'UPDATE ' .ListingItem::class. ' li
             SET li.workedHours = :workedHours
             WHERE li.listing = :listing AND li.day IN (:days)'
        )->setParameters([
            'workedHours' => $workedHours,
            'listing'     => $listing,
            'days'        => $daysToChange
        ])->execute();

        $updatedItems = $items = $this->listingItemsReader
                                      ->findListingItems($listing, $daysToChange);

        return $updatedItems;
    }

    /**
     * @param Listing $listing
     * @param User $recipient
     * @param $description
     * @param array $ignoredListingDays
     * @return Listing
     * @throws DBALException
     */
    public function shareListing(
        Listing $listing,
        User $recipient,
        $description,
        array $ignoredListingDays = []
    ) {
        Validators::assert($description, 'unicode');

        try {
            $this->em->beginTransaction();

            /** @var ListingItem[] $listingItems */
            $listingItems = $this->listingItemsReader
                                 ->findListingItems(
                                     $listing->getId(),
                                     $ignoredListingDays,
                                     true
                                 );

            $newListing = clone $listing;
            $newListing->setUser($recipient);
            $newListing->setDescription($description);
            $newListing->setHourlyWage(null);
            $this->em->persist($newListing);

            foreach ($listingItems as $item) {
                $newItem = clone $item;
                $newItem->setListing($newListing);
                $newItem->setDescription(null);
                $newItem->removeDescOtherHours();
                $this->em->persist($newItem);
            }

            $this->em->flush();
            $this->em->commit();

            return $newListing;

        } catch (DBALException $e) {
            $this->em->rollback();
            $this->em->close();

            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $baseListing
     * @param Listing $listingToMerge
     * @param array $selectedCollisionItems
     * @param User $ownerOfOutputListing
     * @return Listing
     * @throws NoCollisionListingItemSelectedException
     * @throws DBALException
     */
    public function mergeListings(
        Listing $baseListing,
        Listing $listingToMerge,
        array $selectedCollisionItems = [],
        User $ownerOfOutputListing
    ) {
        if (!$this->haveListingsSamePeriod($baseListing, $listingToMerge)) {
            throw new RuntimeException(
                'Given Listings must have same Period(Year and Month).'
            );
        }

        try {
            $this->em->beginTransaction();

            $items = $this->itemsService->getMergedListOfItems(
                $this->listingItemsReader->findListingItems($baseListing->getId()),
                $this->listingItemsReader->findListingItems($listingToMerge->getId()),
                $selectedCollisionItems
            );

            $newListing = new Listing(
                $baseListing->year,
                $baseListing->month,
                $ownerOfOutputListing
            );

            $this->em->persist($newListing);
            foreach ($items as $item) {
                /** @var ListingItem $item */
                $item->setListing($newListing);
                $this->em->persist($item);
            }

            $this->em->flush();
            $this->em->commit();
            return $newListing;

        } catch (DBALException $e) {
            $this->em->rollback();
            $this->em->close();

            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
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