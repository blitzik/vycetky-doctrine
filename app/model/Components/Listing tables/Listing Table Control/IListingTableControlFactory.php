<?php

namespace App\Model\Components\ListingTable;

use App\Model\Query\ListingsQuery;

interface IListingTableControlFactory
{
    /**
     * @param array $listingData
     * @return ListingTableControl
     */
    public function create(array $listingData);
}