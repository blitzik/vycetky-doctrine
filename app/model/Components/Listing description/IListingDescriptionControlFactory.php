<?php

namespace App\Model\Components;

interface IListingDescriptionControlFactory
{
    /**
     * @param \DateTime $period
     * @param $description
     * @return ListingDescriptionControl
     */
    public function create(\DateTime $period, $description);
}