<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 29.12.2015
 */

namespace App\Model\Domain;

use App\Model\Domain\Entities\ListingItem;

class MergeableListingItem extends DisplayableItem
{
    /** @var ListingItem */
    private $listingItem;

    /** @var bool|null */
    private $isFromBaseListing = null;


    public function __construct(ListingItem $listingItem)
    {
        $this->listingItem = $listingItem;
    }


    public function setAsItemFromBaseListing($isFromBaseListing)
    {
        $this->isFromBaseListing = $isFromBaseListing;
    }


    /**
     * @return bool|null
     */
    public function isItemFromBaseListing()
    {
        return $this->isFromBaseListing;
    }
    

    /*
     * -------------------------------------------
     * ----- IDisplayableItem implementation -----
     * -------------------------------------------
     */


    public function isFilling()
    {
        return false;
    }
    

    /*
     * --------------------
     * ----- GETTERS ------
     * --------------------
     */


    public function getListingItemID()
    {
        return $this->listingItem->getId();
    }


    public function getDate()
    {
        return $this->listingItem->getDate();
    }


    public function getDay()
    {
        return $this->listingItem->day;
    }


    public function getListing()
    {
        return $this->listingItem->listing;
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



    public function areWorkedHoursWithoutLunchZero()
    {
        return $this->listingItem->areWorkedHoursWithoutLunchZero();
    }

}

