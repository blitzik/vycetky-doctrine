<?php

namespace App\Model\Services\Writers;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\ORMException;
use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\ListingItemNotFoundException;
use App\Model\Services\Readers\ListingItemsReader;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\ShiftItemDownException;
use Exceptions\Runtime\ShiftItemUpException;
use App\Model\Domain\Entities\ListingItem;
use Kdyby\Doctrine\EntityManager;
use Tracy\Debugger;
use Nette\Object;

class ListingItemsWriter extends Object
{
    const WRITE_DOWN = 1;
    const WRITE_UP   = -1;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ListingItemsReader
     */
    private $listingItemReader;

    public function __construct(
        EntityManager $entityManager,
        ListingItemsReader $listingItemReader
    ) {
        $this->em = $entityManager;
        $this->listingItemReader = $listingItemReader;
    }

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemDayAlreadyExistsException
     * @throws \Exception
     */
    public function saveListingItem(ListingItem $listingItem)
    {
        try {
            $this->em->persist($listingItem)->flush();

        } catch (UniqueConstraintViolationException $u) {
            $this->em->close();

            throw new ListingItemDayAlreadyExistsException;

        } catch (\Exception $e) {
            $this->em->close();

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }

        return $listingItem;
    }

    /**
     * @param ListingItem $listingItem
     * @param string $direction
     * @return ListingItem New or Updated ListingItem
     * @throws ShiftItemUpException
     * @throws ShiftItemDownException
     * @throws \Exception
     */
    public function copyListingItem(
        ListingItem $listingItem,
        $direction
    ) {
        $day = $listingItem->day;
        try {
            if ($direction === self::WRITE_UP) {
                $day += self::WRITE_UP;
                $item = $this->provideItemForUpShifting($listingItem);

            } elseif ($direction === self::WRITE_DOWN) {
                $day += self::WRITE_DOWN;
                $item = $this->provideItemForDownShifting($listingItem);

            } else {
                throw new InvalidArgumentException('Wrong $direction argument. Use WRITE_* constants of ' .self::class);
            }

            $this->updateListingItemProperties($item, $listingItem);

        } catch (ListingItemNotFoundException $e) {
            $item = clone $listingItem;
            $item->setDay($day);
        }

        return $this->saveListingItem($item);
    }

    /**
     * @param ListingItem $new
     * @param ListingItem $original
     * @return void
     */
    private function updateListingItemProperties(
        ListingItem $new,
        ListingItem $original
    ) {
        $new->setLocality($original->getLocality());
        $new->setWorkedTime(
            $original->getWorkedHours(),
            $original->descOtherHours
        );
        $new->setDescription($original->description);
    }

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemNotFoundException
     */
    private function provideItemForDownShifting(ListingItem $listingItem)
    {
        // we do NOT want to shift the last item
        if ($listingItem->day >= $listingItem->getListing()->getNumberOfDaysInMonth()) {
            throw new ShiftItemDownException;
        }

        return $this->listingItemReader->getNextItem($listingItem);
    }

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemNotFoundException
     */
    private function provideItemForUpShifting(ListingItem $listingItem)
    {
        // we do NOT want to shift the first item
        if ($listingItem->day <= 1) {
            throw new ShiftItemUpException;
        }

        return $this->listingItemReader->getPreviousItem($listingItem);
    }
}