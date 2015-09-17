<?php

namespace App\Model\Components;

interface IListingsOverviewControlFactory
{
    /**
     * @return ListingsOverviewControl
     */
    public function create();
}