<?php

namespace App\Model\Components;

use App\Model\ResultObjects\ListingResult;

interface IListingRemovalControlFactory
{
    /**
     * @param ListingResult $listingResult
     * @return ListingRemovalControl
     */
    public function create(ListingResult $listingResult);
}