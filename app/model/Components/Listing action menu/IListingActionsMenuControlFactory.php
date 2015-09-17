<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IListingActionsMenuControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingActionsMenuControl
     */
    public function create(Listing $listing);
}