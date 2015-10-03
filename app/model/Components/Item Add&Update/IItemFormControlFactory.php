<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Listing;

interface IItemFormControlFactory
{
    /**
     * @param Listing $listing
     * @param int $day
     * @return ItemFormControl
     */
    public function create(Listing $listing, $day);
}