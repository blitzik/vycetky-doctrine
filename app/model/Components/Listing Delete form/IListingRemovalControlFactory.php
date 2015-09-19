<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IListingRemovalControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingRemovalControl
     */
    public function create(Listing $listing);
}