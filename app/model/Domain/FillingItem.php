<?php

namespace App\Model\Domain;

use Nette\Object;

class FillingItem extends Object implements IDisplayableItem
{
    /**
     * @var \DateTime
     */
    protected $date;

    public function __construct(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * @return true
     */
    public function isFilling()
    {
        return true;
    }

    /**
     * @return \DateTime
     */
    public function getDay()
    {
        return $this->date;
    }

    /**
     * @return bool
     */
    public function isWeekDay()
    {
        $d = date_format($this->getDay(), 'w');

        return ($d > 0 && $d < 6) ? true : false;
    }

    /**
     * @return bool
     */
    public function isCurrentDay()
    {
        if ($this->getDay()->format('Y-m-d') == (new \DateTime('now'))->format('Y-m-d'))
            return true;

        return false;
    }
}