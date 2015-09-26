<?php

namespace App\Model\Components\ListingTable;

use App\Model\Domain\Entities\Listing;

interface IListingTableControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingTableControl
     */
    public function create(Listing $listing);
}