<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IListingPDFGenerationControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingPDFGenerationControl
     */
    public function create(Listing $listing);
}