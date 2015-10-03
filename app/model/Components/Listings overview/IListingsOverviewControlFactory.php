<?php

namespace App\Model\Components;

use App\Model\Queries\Listings\ListingsForOverviewQuery;

interface IListingsOverviewControlFactory
{
    /**
     * @param ListingsForOverviewQuery $listingsQuery
     * @return ListingsOverviewControl
     */
    public function create(ListingsForOverviewQuery $listingsQuery);
}