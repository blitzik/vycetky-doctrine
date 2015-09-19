<?php

namespace Filters;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Time\TimeUtils;
use Nette\Object;
use Nette\Utils\Validators;

class FilterLoader extends Object
{

    public function loader($filter)
    {
        if (!method_exists($this, $filter)) {
            return null;
        }

        return call_user_func_array([$this, $filter], array_slice(func_get_args(), 1));
    }

    /**
     * @param $time
     * @param bool $isZeroVisible
     * @return int|string
     */
    public function toTimeWithComma($time, $isZeroVisible = false)
    {
        if (!$time instanceof \InvoiceTime) {
            $time = new \InvoiceTime($time);
        }

        if ($time->getTime() == '00:00:00') {
            if ($isZeroVisible === false)
                return '';
            else
                return 0;
        }

        return $time->toTimeWithComma();
    }

    /**
     * @param int $day
     * @return string
     */
    public function dayName($day)
    {
        return TimeUtils::getDayName(date_format($day, 'w'));
    }

    /**
     * @param \DateTime $day
     * @return string
     */
    public function dayShortcut(\DateTime $day)
    {
        return TimeUtils::getDayName(date_format($day, 'w'), true);
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    public function listingMonthYear(\DateTime $date)
    {
        return $this->monthName($date) . ' ' .  date_format($date, 'Y');
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    public function monthName(\DateTime $date)
    {
        return TimeUtils::getMonthName((int)date_format($date, 'm'));
    }

    public function monthNameByNumber($number)
    {
        return TimeUtils::getMonthName($number);
    }

    /**
     * @param $numberOfDays
     * @return string
     */
    public function dayWordForm($numberOfDays)
    {
        $text = $numberOfDays;
        if ($numberOfDays == 1)
            $text .= ' den';
        elseif ($numberOfDays > 1 and $numberOfDays < 5)
            $text .= ' dny';
        elseif ($numberOfDays == 0 or $numberOfDays > 4)
            $text .= ' dní';

        return $text;
    }

    /**
     * @param \DateTime $date
     * @return bool
     */
    public function isCurrentDay(\DateTime $date)
    {
        if ($date->format('Y-m-d') == (new \DateTime('now'))->format('Y-m-d'))
            return true;

        return false;
    }

    /**
     * @param \DateTime $date
     * @return bool
     */
    public function isWeekDay(\DateTime $date)
    {
        $d = date_format($date, 'w');

        return ($d > 0 && $d < 6) ? true : false;
    }
}