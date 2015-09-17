<?php

namespace App\Model\Services\Managers;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Query\ListingItemsQuery;
use App\Model\Services\Readers\ListingItemReader;
use App\Model\Services\Writers\ListingItemWriter;
use Doctrine\DBAL\LockMode;
use Exceptions\Runtime\DayExceedCurrentMonthException;
use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\ListingItemNotFoundException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;
use Nette\Object;

class ListingItemManager extends Object
{
    /**
     * @var EntityRepository
     */
    private $listingItemRepository;

    /**
     * @var ListingItemWriter
     */
    private $listingItemWriter;

    /**
     * @var ListingItemReader
     */
    private $listingItemReader;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        ListingItemWriter $listingItemWriter,
        ListingItemReader $listingItemReader,
        EntityManager $entityManager
    ) {
        $this->listingItemWriter = $listingItemWriter;
        $this->listingItemReader = $listingItemReader;
        $this->em = $entityManager;

        $this->listingItemRepository = $this->em->getRepository(ListingItem::class);
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
        $listingItem = $this->listingItemWriter->saveListingItem($newValues, $listingItem);

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
     * @return mixed
     * @throws DayExceedCurrentMonthException
     * @throws ListingItemNotFoundException
     * @throws \Exception
     */
    public function shiftCopyOfListingItemDown(
        $day,
        Listing $listing
    ) {
        // we do NOT want to shift the last item
        if ($day >= $listing->getNumberOfDaysInMonth()) {
            throw new DayExceedCurrentMonthException;
        }

        $currentItemQuery = new ListingItemsQuery();
        $currentItemQuery->byListing($listing)
                         ->byDay($day);

        $nextItemQuery = new ListingItemsQuery();
        $nextItemQuery->byListing($listing)
                      ->byDay($day + 1);

        try {
            $this->em->beginTransaction();

            /** @var ListingItem $listingItem */
            $listingItem = $this->listingItemReader->fetchListingItem($currentItemQuery);
            $this->em->lock($listingItem, LockMode::PESSIMISTIC_READ);

            try {
                /** @var ListingItem $nextListingItem */
                $nextListingItem = $this->listingItemReader->fetchListingItem($nextItemQuery);
                $nextListingItem->setLocality($listingItem->getLocality());
                $nextListingItem->setWorkedTime(
                    $listingItem->getWorkedHours(),
                    $listingItem->descOtherHours
                );
                $nextListingItem->setDescription($listingItem->description);

            } catch (ListingItemNotFoundException $li) {
                $nextListingItem = clone $listingItem;
                $nextListingItem->setDay($day + 1);
            }

            $this->em->persist($nextListingItem)->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->em->close();

            throw $e;
        }

        return $nextListingItem;
    }

}