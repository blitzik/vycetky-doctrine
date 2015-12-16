<?php

namespace App\Model\Pdf\Listing\DataAdapters;

use App\Model\Services\ItemsService;
use Nette\Object;

class ListingPdfDataAdapter extends Object implements IListingPdfDataAdapter
{
    /** @var ItemsService */
    private $itemsService;

    /** @var array */
    private $listing;

    /** @var array */
    private $items;

    /** @var array */
    private $entireListingItemsCollection;

    /** @var \DateTime */
    private $period;

    public function __construct(array $data, ItemsService $itemsService)
    {
        $this->itemsService = $itemsService;
        $this->listing = $data['listing'];
        $this->items = $data['items'];

        $items = $this->itemsService->convert2DisplayableItems($this->items);
        $this->entireListingItemsCollection = $this->itemsService
                                                   ->generateEntireTable($items, \DateTime::createFromFormat('!Y-m', $this->listing['l_year'].'-'.$this->listing['l_month']));
    }



    /**
     * @return int
     */
    public function getListingId()
    {
        return (int)$this->listing['l_id'];
    }



    /**
     * @return int
     */
    public function getListingYear()
    {
        return (int)$this->listing['l_year'];
    }



    /**
     * @return int
     */
    public function getListingMonth()
    {
        return (int)$this->listing['l_month'];
    }



    /**
     * @return \DateTime
     */
    public function getPeriod()
    {
        if (!isset($this->period)) {
            $this->period = \DateTime::createFromFormat('!Y-m', $this->getListingYear().'-'.$this->getListingMonth());
        }

        return $this->period;
    }


    /**
     * @return string|null
     */
    public function getListingDescription()
    {
        return $this->listing['l_description'];
    }



    /**
     * @return int|null
     */
    public function getListingHourlyWage()
    {
        return (int)$this->listing['l_hourlWage'];
    }



    /**
     * @return \InvoiceTime
     */
    public function getTotalWorkedHours()
    {
        return new \InvoiceTime((int)$this->listing['total_worked_hours_in_sec']);
    }



    /**
     * @return \InvoiceTime
     */
    public function getWorkedHours()
    {
        return new \InvoiceTime($this->listing['worked_hours']);
    }



    /**
     * @return \InvoiceTime
     */
    public function getLunchHours()
    {
        return new \InvoiceTime($this->listing['lunch_hours']);
    }



    /**
     * @return \InvoiceTime
     */
    public function getOtherHours()
    {
        return new \InvoiceTime($this->listing['other_hours']);
    }



    /*
     * -------------------------
     * ----- OWNER GETTERS -----
     * -------------------------
     */



    /**
     * @return int
     */
    public function getOwnerId()
    {
        return (int)$this->listing['u_id'];
    }



    /**
     * @return string
     */
    public function getOwnerUsername()
    {
        return $this->listing['u_username'];
    }



    /**
     * @return string
     */
    public function getOwnerName()
    {
        return $this->listing['u_name'];
    }



    /*
     * -------------------------
     * ----- LISTING ITEMS -----
     * -------------------------
     */



    public function getEntireListingCollection()
    {
        return $this->entireListingItemsCollection;
    }

}