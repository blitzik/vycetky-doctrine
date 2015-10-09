<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\User;

interface IListingFormControlFactory
{
    /**
     * @param Listing|NULL $listing
     * @param User $owner
     * @return ListingFormControl
     */
    public function create($listing, User $owner);
}