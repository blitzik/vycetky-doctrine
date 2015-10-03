<?php

namespace App\Model\ResultObjects;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\Entities\Listing;
use Nette\Object;

class ListingResult extends Object
{
    /** @var int */
    private $workedDays;

    /** @var \InvoiceTime  */
    private $workedHours;

    /** @var \InvoiceTime  */
    private $totalWorkedHours;

    /** @var \InvoiceTime  */
    private $lunchHours;

    /** @var \InvoiceTime  */
    private $otherHours;

    /**
     * @var Listing
     */
    private $listing;

    public function __construct($result)
    {
        if ($result instanceof Listing) {
            $this->listing = $result;

        } elseif (is_array($result)) {
            if (isset($result[0])) { // Listing Entity
                if (!$result[0] instanceof Listing) {
                    throw new InvalidArgumentException(
                        'Wrong type of entity in $result.'
                    );
                } else {
                    $this->listing = $result[0];
                }
            }

            $this->workedDays = (int)$result['worked_days'];
            $this->totalWorkedHours = new \InvoiceTime((int)$result['total_worked_hours_in_sec']);
            $this->workedHours = new \InvoiceTime($result['worked_hours']);
            $this->lunchHours = new \InvoiceTime($result['lunch_hours']);
            $this->otherHours = new \InvoiceTime($result['other_hours']);
        } else {
            throw new InvalidArgumentException(
                'Only array or instance of ' .Listing::class. ' are allowed'
            );
        }
    }

    /**
     * @return int
     */
    public function getWorkedDays()
    {
        return $this->workedDays;
    }

    /**
     * @return \InvoiceTime
     */
    public function getWorkedHours()
    {
        return $this->workedHours;
    }

    /**
     * @return \InvoiceTime
     */
    public function getTotalWorkedHours()
    {
        return $this->totalWorkedHours;
    }

    /**
     * @return \InvoiceTime
     */
    public function getLunchHours()
    {
        return $this->lunchHours;
    }

    /**
     * @return \InvoiceTime
     */
    public function getOtherHours()
    {
        return $this->otherHours;
    }

    /**
     * @return Listing
     */
    public function getListing()
    {
        return $this->listing;
    }
}