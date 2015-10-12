<?php

namespace App\Model\Services\Managers;

use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use App\Model\Services\Providers\WorkedHoursProvider;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use App\Model\Services\Providers\LocalityProvider;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\Entities\WorkedHours;
use App\Model\Domain\Entities\ListingItem;
use App\Model\Domain\Entities\Locality;
use App\Model\Domain\Entities\Listing;
use Nette\Object;

class ListingItemsManager extends Object
{
    /** @var WorkedHoursProvider  */
    private $workedHoursProvider;

    /** @var LocalityProvider  */
    private $localityProvider;

    public function __construct(
        WorkedHoursProvider $workedHoursProvider,
        LocalityProvider $localityProvider
    ) {
        $this->workedHoursProvider = $workedHoursProvider;
        $this->localityProvider = $localityProvider;
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

        $locality = new Locality($newValues['locality'], $newValues['user']);

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

}