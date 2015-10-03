<?php

namespace App\Model\Components;

use App\Model\ResultObjects\ListingResult;

interface IMassItemsChangeControlFactory
{
    /**
     * @param ListingResult $listingResult
     * @return MassItemsChangeControl
     */
    public function create(ListingResult $listingResult);
}