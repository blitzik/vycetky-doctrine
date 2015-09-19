<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IMassItemsChangeControlFactory
{
    /**
     * @return MassItemsChangeControl
     */
    public function create(Listing $listing);
}