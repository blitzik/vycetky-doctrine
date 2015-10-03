<?php

namespace App\Model\Components;

use App\Model\ResultObjects\ListingResult;

interface IListingPDFGenerationControlFactory
{
    /**
     * @param ListingResult $listingResult
     * @return ListingPDFGenerationControl
     */
    public function create(ListingResult $listingResult);
}