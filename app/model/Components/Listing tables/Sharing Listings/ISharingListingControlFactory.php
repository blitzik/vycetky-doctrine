<?php

namespace App\Model\Components;

use App\Model\Entities\Listing;

interface ISharingListingControlFactory
{
    /**
     * @param Listing $listing
     * @return SharingListingControl
     */
    public function create(Listing $listing);
}