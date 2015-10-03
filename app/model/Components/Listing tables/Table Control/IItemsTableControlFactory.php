<?php

namespace App\Model\Components\ItemsTable;

use App\Model\ResultObjects\ListingResult;

interface IItemsTableControlFactory
{
    /**
     * @param ListingResult $listingResult
     * @return ItemsTableControl
     */
    public function create(ListingResult $listingResult);
}