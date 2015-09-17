<?php

namespace App\Model\Components;

interface IFilterControlFactory
{
    /**
     * @return FilterControl
     */
    public function create();
}