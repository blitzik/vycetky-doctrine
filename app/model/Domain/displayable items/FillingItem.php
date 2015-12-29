<?php

namespace App\Model\Domain;

class FillingItem extends DisplayableItem
{
    /** @var \DateTime */
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
}