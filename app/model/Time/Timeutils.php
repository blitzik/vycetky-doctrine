<?php

namespace App\Model\Time;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use Nette\Object;

final class TimeUtils extends Object
{
    private static $months = array(1 => 'Leden', 2 => 'Únor', 3 => 'Březen',
                                   4 => 'Duben', 5 => 'Květen', 6 => 'Červen',
                                   7 => 'Červenec', 8 => 'Srpen', 9 => 'Září',
                                   10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec');

    private static $days = array(0 => 'Neděle', 1 => 'Pondělí', 2 => 'Úterý',
                                 3 => 'Středa', 4 => 'Čtvrtek', 5 => 'Pátek',
                                 6 => 'Sobota');

    /**
     * @return array Returns array of Months
     */
    public static function getMonths()
    {
        return self::$months;
    }


    /**
     * Getter for array that contain Days of the week. (start with 0 key for Sunday)
     *
     * @return array Return array of Days in week
     */
    public static function getDays()
    {
        return self::$days;
    }

    /**
     * @param int $monthNumber
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getMonthName($monthNumber)
    {
        Validators::assert($monthNumber, 'numericint:1..12');

        return self::$months[$monthNumber];
    }


    /**
     * @param string $monthName
     * @return int
     * @throws InvalidArgumentException
     */
    public static function getMonthNumberByName($monthName)
    {
        $months = array_flip(self::$months);
        if (!array_key_exists(ucfirst($monthName), $months))
            throw new InvalidArgumentException(
                'Month with this name doesn\'t
                 exist. Given "' . $monthName . '"'
            );

        return self::$months[$monthName];
    }


    /**
     * @param int $dayNumber
     * @return string
     */
    public static function getDayName($dayNumber, $getShortcut = false)
    {
        Validators::assert($dayNumber, 'numericint:0..6');
        Validators::assert($getShortcut, 'bool');

        if ($getShortcut === true) {
            return \mb_strtoupper(\mb_substr(self::$days[$dayNumber], 0, 2), 'UTF-8');
        }

        return self::$days[$dayNumber];
    }

    /**
     * Generates array of years for Select component of Form.
     * Every year this method increases automatically its output by current year
     *
     * @return array Array of years
     * @throws InvalidArgumentException
     */
    public static function generateYearsForSelect()
    {
        $base = 2014;
        $currentYear = (int)strftime('%Y');

        $result = array_combine(
            range($base, $currentYear),
            range($base, $currentYear)
        );
        krsort($result);

        return $result;
    }

    /**
     *
     * @param int $year
     * @param int $month
     * @param int|null $day
     * @return boolean|\DateTime Returns DateTime if the given date is valid, otherwise returns FALSE
     */
    public static function getDateTimeFromParameters($year, $month, $day = null)
    {
        if (!Validators::is($year, 'numericint') or
            !Validators::is($month, 'numericint') or
            !Validators::is($day, 'null|numericint')) {
            return FALSE;
        }

        if ($day == null) {
            if (\checkdate($month, 1, $year)) {
                return new \DateTime($year.'-'.$month);
            }
        }

        if (\checkdate($month, $day, $year)) {
            return new \DateTime($year.'-'.$month.'-'.$day);
        }

        return FALSE;
    }

    /**
     * @param $year
     * @param $month
     * @return int
     */
    public static function getNumberOfDaysInMonth($year, $month)
    {
        return \cal_days_in_month(
            CAL_GREGORIAN,
            $month,
            $year
        );
    }
}