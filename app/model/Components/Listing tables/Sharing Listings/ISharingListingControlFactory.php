<?php

namespace App\Model\Components;

use App\Model\ResultObjects\ListingResult;

interface ISharingListingControlFactory
{
    /**
     * @param ListingResult $listing
     * @return SharingListingControl
     */
    public function create(ListingResult $listing);
}