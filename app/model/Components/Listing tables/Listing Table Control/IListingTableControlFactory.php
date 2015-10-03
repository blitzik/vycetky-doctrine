<?php

namespace App\Model\Components\ListingTable;

use App\Model\ResultObjects\ListingResult;

interface IListingTableControlFactory
{
    /**
     * @param ListingResult $listingResult
     * @return ListingTableControl
     */
    public function create(ListingResult $listingResult);
}