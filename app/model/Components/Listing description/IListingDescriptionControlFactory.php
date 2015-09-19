<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IListingDescriptionControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingDescriptionControl
     */
    public function create(Listing $listing);
}