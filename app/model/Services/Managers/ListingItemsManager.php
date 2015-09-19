<?php

namespace App\Model\Services\Managers;

use App\Model\Domain\IDisplayableItem;
use App\Model\Services\ItemsService;
use Doctrine\ORM\ORMException;
use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use App\Model\Services\Providers\WorkedHoursProvider;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use App\Model\Services\Providers\LocalityProvider;
use App\Model\Services\Readers\ListingItemsReader;
use App\Model\Services\Writers\ListingItemsWriter;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\ShiftItemDownException;
use Exceptions\Runtime\ShiftItemUpException;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Query\ListingItemsQuery;
use App\Model\Domain\Entities\Listing;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Doctrine\DBAL\LockMode;
use Tracy\Debugger;
use Nette\Object;

class ListingItemsManager extends Object
{
    /**
     * @var EntityRepository
     */
    private $listingItemRepository;

    /**
     * @var ListingItemsWriter
     */
    private $listingItemsWriter;

    /**
     * @var ListingItemsReader
     */
    private $listingItemsReader;

    /**
     * @var WorkedHoursProvider
     */
    private $workedHoursProvider;

    /**
     * @var LocalityProvider
     */
    private $localityProvider;

    /**
     * @var ItemsService
     */
    private $itemsService;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        ListingItemsWriter $listingItemWriter,
        ListingItemsReader $listingItemReader,
        WorkedHoursProvider $workedHoursProvider,
        LocalityProvider $localityProvider,
        ItemsService $itemsService,
        EntityManager $entityManager
    ) {
        $this->listingItemsWriter = $listingItemWriter;
        $this->listingItemsReader = $listingItemReader;
        $this->workedHoursProvider = $workedHoursProvider;
        $this->localityProvider = $localityProvider;
        $this->itemsService = $itemsService;
        $this->em = $entityManager;

        $this->listingItemRepository = $this->em->getRepository(ListingItem::class);
    }

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemDayAlreadyExistsException
     * @throws \Exception
     */
    public function saveListingItem(ListingItem $listingItem)
    {
        $this->listingItemsWriter->saveListingItem($listingItem);

        return $listingItem;
    }

    /**
     * @param array $newValues
     * @param ListingItem|null $listingItem
     * @return ListingItem
     * @throws OtherHoursZeroTimeException
     * @throws NegativeResultOfTimeCalcException
     * @throws ShiftEndBeforeStartException
     */
    public function prepareListingItemByFormsData(array $newValues, ListingItem $listingItem = null)
    {
        $workedHours = new WorkedHours(
            $newValues['workStart'], $newValues['workEnd'],
            $newValues['lunch'], $newValues['otherHours']
        );

        $locality = new Locality($newValues['locality']);

        if (isset($listingItem)) {
            if (!$listingItem->getWorkedHours()->hasSameValuesAs($workedHours)) {
                $workedHours = $this->workedHoursProvider->setupWorkedHoursEntity($workedHours);
                $listingItem->setWorkedTime($workedHours, $newValues['descOtherHours']);
            }

            if (!$listingItem->getLocality()->isSameAs($locality)) {
                $locality = $this->localityProvider->setupLocalityEntity($locality);
                $listingItem->setLocality($locality);
            }

            $listingItem->setDescription($newValues['description']);
        } else {

            $day = $newValues['day'];

            if (!$newValues['listing'] instanceof Listing) {
                throw new InvalidArgumentException('Argument $newValues must have member "listing " of type ' .Listing::class);
            }
            $listing = $newValues['listing'];

            $locality = $this->localityProvider->setupLocalityEntity($locality);
            $workedHours = $this->workedHoursProvider->setupWorkedHoursEntity($workedHours);

            $listingItem = new ListingItem(
                $day,
                $listing,
                $workedHours,
                $locality,
                $newValues['description'],
                $newValues['descOtherHours']
            );
        }

        return $listingItem;
    }

    /**
     * @param $day
     * @param Listing $listing
     */
    public function removeListingItem($day, Listing $listing)
    {
        $this->em->createQuery(
            'DELETE ' .ListingItem::class. ' l
             WHERE l.listing = :listing AND l.day = :day'
        )->execute(['listing' => $listing, 'day' => $day]);
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @param int $direction
     * @return ListingItem
     * @throws ShiftItemDownException
     * @throws ShiftItemUpException
     * @throws \Exception
     */
    public function copyListingItem(
        $day,
        Listing $listing,
        $direction
    ) {
        $currentItemQuery = new ListingItemsQuery();
        $currentItemQuery->byListing($listing)
                         ->byDay($day);

        try {
            $this->em->beginTransaction();

            /** @var ListingItem $listingItem */
            $listingItem = $this->listingItemsReader
                                ->fetchListingItem($currentItemQuery);
            $this->em->lock($listingItem, LockMode::PESSIMISTIC_READ);

            $shiftedItem = $this->listingItemsWriter
                                ->copyListingItem($listingItem, $direction);

            $this->em->commit();

            return $shiftedItem;

        } catch (\Exception $e) {
            $this->em->close();

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

}