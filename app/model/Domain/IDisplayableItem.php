<?php

namespace App\Model\Domain;

interface IDisplayableItem
{
    /**
     * @return bool
     */
    public function isFilling();

    /**
     * @return bool
     */
    public function isWeekDay();

    /**
     * @return bool
     */
    public function isCurrentDay();

    /**
     * @return \DateTime
     */
    public function getDay();
}