<?php

namespace App\Model\Time;

use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Logic\InvalidArgumentException;
use Nette\Object;

class TimeManipulator extends Object
{
    const SECS_IN_MINUTE = 60;
    const SECS_IN_HOUR = 3600;

    const SECS_MAX_LIMIT = 2147482799;
    const SECS_MIN_LIMIT = -2147482799;

    /**
     * @param int $seconds
     * @return string
     * @throws InvalidArgumentException
     */
    public static function seconds2time($seconds)
    {
        if (!is_int($seconds) or $seconds < self::SECS_MIN_LIMIT or $seconds > self::SECS_MAX_LIMIT) {
            throw new InvalidArgumentException(
                'Argument $seconds must be INTEGER number between
                ' . self::SECS_MIN_LIMIT . ' and ' . self::SECS_MAX_LIMIT .
                '. [' . $seconds . '] given instead.'
            );
        }

        $sign = $seconds < 0 ? '-' : '';
        $seconds = abs($seconds);

        return $sign . sprintf(
            '%02d:%02d:%02d',
            ($seconds / self::SECS_IN_HOUR),
            ($seconds / self::SECS_IN_MINUTE % self::SECS_IN_MINUTE),
            ($seconds % self::SECS_IN_MINUTE)
        );
    }

    /**
     * @param \DateTime|string $time
     * @return float
     * @throws InvalidArgumentException
     */
    public static function time2seconds($time)
    {
        if (!self::isTimeFormatValid($time)) {
            throw new InvalidArgumentException(
                'Argument $time has wrong format. ' . '"'.$time.'" given.'
            );
        }

        list($hours, $minutes, $seconds) = sscanf($time, '%d:%d:%d');
        if (abs($hours) > 596522) {
            throw new InvalidArgumentException(
                'Argument $time has exceeded maximal value (596522:59:59).' .
                '"'.$time.'" given.'
            );
        }

        $sign = strpos($time, '-') !== false ? -1 : 1;

        return $sign * ((abs($hours) * self::SECS_IN_HOUR) +
                ($minutes * self::SECS_IN_MINUTE) +
                 $seconds);
    }



    /**
     * @param mixed $time
     * @return boolean
     */
    public static function isTimeFormatValid($time)
    {
        if (!preg_match('~^-?\d+:[0-5][0-9]:[0-5][0-9]$~', $time)) {
            return false;
        }

        return true;
    }

    /**
     * @param array Array must contain 2 parameters at least
     * @param string $order Type "asc" or "desc" to order
     * @return mixed
     * @throws NegativeResultOfTimeCalcException
     */
    public static function subTimes(array $times, $order = null)
    {
        if (count($times) < 2)
            throw new InvalidArgumentException(
                'Method has to accept 2 parameters at least.'
            );

        $t = [];
        foreach ($times as $key => $time) {
            $t[] = self::time2seconds($time);
        }

        if (isset($order)) {
            switch ($order) {
                case 'asc'  : sort($t);  break;
                case 'desc' : rsort($t); break;
            }
        }

        $result = $t[0];
        $count = count($t);
        for ($i = 1; $i < $count; $i++) {
            $result -= $t[$i];
        }

        return self::seconds2time($result);
    }

    /**
     * Method adds up all given parameters
     *
     * @param array Method has to have more than 2 time parameters
     * @return mixed
     */
    public static function sumTimes(array $times)
    {
        if (count($times) < 2)
            throw new InvalidArgumentException(
                'Method has to accept 2 parameters at least.'
            );

        $result = 0;
        foreach ($times as $time) {
            $result += self::time2seconds($time);
        }

        return self::seconds2time($result);
    }

    /**
     * Method compares given times
     *
     * @param mixed $time1
     * @param mixed $time2
     * @return boolean Return TRUE if times are even otherwise false
     */
    public static function areTimesEven($time1, $time2)
    {
        $time1 = self::time2seconds($time1);
        $time2 = self::time2seconds($time2);

        return ($time1 === $time2);
    }

}