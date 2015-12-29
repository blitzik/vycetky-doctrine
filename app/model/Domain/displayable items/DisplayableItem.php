<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 29.12.2015
 */

namespace App\Model\Domain;

use Nette\Object;

abstract class DisplayableItem extends Object implements IDisplayableItem
{
    /** @var \DateTime */
    protected $date;


    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }


    /**
     * @return bool
     */
    public function isWeekDay()
    {
        $d = date_format($this->getDate(), 'w');

        return ($d > 0 && $d < 6) ? true : false;
    }


    /**
     * @return bool
     */
    public function isCurrentDay()
    {
        if ($this->getDate()->format('Y-m-d') == (new \DateTime('now'))->format('Y-m-d'))
            return true;

        return false;
    }
}