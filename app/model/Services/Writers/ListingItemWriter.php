<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Services\Providers\LocalityProvider;
use App\Model\Services\Providers\WorkedHoursProvider;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;

class ListingItemWriter extends Object
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LocalityProvider
     */
    private $localityProvider;

    /**
     * @var WorkedHoursProvider
     */
    private $workedHoursProvider;

    public function __construct(
        EntityManager $entityManager,
        LocalityProvider $localityProvider,
        WorkedHoursProvider $workedHoursProvider
    ) {
        $this->em = $entityManager;
        $this->localityProvider = $localityProvider;
        $this->workedHoursProvider = $workedHoursProvider;
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
}