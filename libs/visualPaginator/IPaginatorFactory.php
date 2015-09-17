<?php

namespace Components;

interface IPaginatorFactory
{
    /**
     * @return VisualPaginator
     */
    public function create();
}