<?php

namespace App\Model\Domain;

use App\Model\Domain\Entities\ListingItem;
use App\Model\Time\TimeUtils;
use Nette\Utils\Validators;

class ListingItemDecorator extends FillingItem
{
    /** @var ListingItem */
    private $listingItem;

    /** @var bool|null */
    private $isFromBaseListing = null;

    public function __construct(
        ListingItem $listingItem
    ) {
        $this->listingItem = $listingItem;

        $year = $listingItem->listing->year;
        $month = $listingItem->listing->month;

        $this->date = TimeUtils::getDateTimeFromParameters(
            $year,
            $month,
            $listingItem->day
        );
    }

    /**
     * @return false
     */
    public function isFilling()
    {
        return false;
    }

    /**
     * @ bool
     */
    public function setAsItemFromBaseListing($bool)
    {
        Validators::assert($bool, 'bool');

        $this->isFromBaseListing = $bool;
    }

    /**
     * @return bool|null
     */
    public function isItemFromBaseListing()
    {
        return $this->isFromBaseListing;
    }

    public function getListing()
    {
        return $this->listingItem->listing;
    }

    public function getListingItemID()
    {
        return $this->listingItem->getId();
    }

    public function getLocality()
    {
        return $this->listingItem->locality->name;
    }

    public function getWorkStart()
    {
        return $this->listingItem->workedHours->workStart;
    }

    public function getWorkEnd()
    {
        return $this->listingItem->workedHours->workEnd;
    }

    public function getLunch()
    {
        return $this->listingItem->workedHours->lunch;
    }

    public function getHours()
    {
        return $this->listingItem->workedHours->hours;
    }

    public function getOtherHours()
    {
        return $this->listingItem->workedHours->otherHours;
    }

    public function getDescOtherHours()
    {
        return $this->listingItem->descOtherHours;
    }

    public function getDescription()
    {
        return $this->listingItem->description;
    }

    /**
     * @return bool
     */
    public function areWorkedHoursWithoutLunchZero()
    {
        $workedHours = $this->getWorkEnd()->subTime($this->getWorkStart());

        return ($workedHours->compare('00:00:00') === 0) ? true : false;
    }

}