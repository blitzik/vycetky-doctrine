<?php

namespace App\Model\Components;

use App\Model\Entities\Listing;

interface IListingFormControlFactory
{
    /**
     * @param Listing|NULL $listing
     * @return ListingFormControl
     */
    public function create($listing);
}