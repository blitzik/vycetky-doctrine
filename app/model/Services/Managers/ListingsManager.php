<?php

namespace App\Model\Services\Managers;

use App\Model\Domain\Entities\WorkedHours;
use App\Model\Services\Providers\WorkedHoursProvider;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Services\Writers\ListingsWriter;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Nette\Utils\Validators;
use Tracy\Debugger;

class ListingsManager extends Object
{
    /**
     * @var WorkedHoursProvider
     */
    private $workedHoursProvider;

    /**
     * @var ListingItemsReader
     */
    private $listingItemsReader;

    /**
     * @var ListingsWriter
     */
    private $listingsWriter;
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        WorkedHoursProvider $workedHoursProvider,
        ListingItemsReader $listingItemsReader,
        ListingsWriter $listingsWriter,
        EntityManager $entityManager
    ) {
        $this->workedHoursProvider = $workedHoursProvider;
        $this->listingItemsReader = $listingItemsReader;
        $this->listingsWriter = $listingsWriter;
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
     */
    public function removeListing(Listing $listing)
    {
        $this->em->remove($listing)->flush();
    }

    /**
     * @param Listing $listing
     * @param bool $withItems
     * @param array|null $valuesForNewListing
     * @return Listing
     * @throws \Exception
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
            $items = $this->listingItemsReader->findListingItems($listing);
            if (!empty($items)) {
                foreach ($items as $item) {
                    /** @var ListingItem $newItem */
                    $newItem = clone $item;
                    $newItem->setListing($newListing);

                    $this->em->persist($newItem);
                }
            }
        }

        try {
            $this->em->flush();
            $this->em->clear();

        } catch (\Exception $e) {
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }

        return $newListing;
    }

    public function baseListingOn(
        Listing $listing,
        array $newListingItems
    ) {
        // todo
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
}