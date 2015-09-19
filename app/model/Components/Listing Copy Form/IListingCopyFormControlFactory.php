<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IListingCopyFormControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingCopyFormControl
     */
    public function create(Listing $listing);
}