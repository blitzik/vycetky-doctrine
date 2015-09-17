<?php

use \Exceptions\Logic\InvalidArgumentException;
use App\Model\Time\TimeManipulator;

class InvoiceTime extends \Nette\Object
{
    const TIME_REGEXP = '~^\d+:[03]0:00$~';
    const TIME_STEP = 1800;

    /**
     * @var string
     */
    private $time;


    /* *** formats *** */
    private $timeWithComma;
    private $hoursAndMinutes;
    private $numberOfSeconds;

    /**
     * There is different behaviour based on given value and its data type.
     *
     * NULL                   : sets object to 00:00:00
     * InvoiceTime            : sets object to InvoiceTime time
     * Integer                : integer means number of seconds that must be
     *                          positive and divisible by 1800 without reminder.
     * DateTime               : object takes only the time part
     * String [e.g. 43:30:00] : sets this exact time
     * String [e.g. 43:30]    : hours and minutes time part
     * String [e.g 9 or 9,5]  : hours and minutes special format. ( but NOT 9,0)
     *
     * @param DateTime|InvoiceTime|int|string|null $time
     */
    public function __construct($time = null)
    {
        if ($time === null) {
            $time = '00:00:00';
        }

        $this->time = self::gatherTime($time);
    }

    private static function timeWithComma2Time($timeWithComma)
    {
        $hours = str_replace(',', '.', $timeWithComma);

        return TimeManipulator::seconds2time((int)(TimeManipulator::SECS_IN_HOUR * $hours));
    }

    /**
     * @param DateTime|InvoiceTime|int|string|null $time
     * @return string time in format HH..:MM:SS
     */
    public static function processTime($time)
    {
        return self::gatherTime($time);
    }

    /**
     * @param DateTime|InvoiceTime|int|string|null $time
     * @return InvoiceTime
     */
    public function sumWith($time)
    {
        $baseTime = self::gatherTime($time);

        $result = TimeManipulator::sumTimes([$baseTime, $this->getTime()]);

        return new self($result);
    }

    /**
     * @param DateTime|InvoiceTime|int|string|null $time
     * @return InvoiceTime
     * @throws \Exceptions\Runtime\NegativeResultOfTimeCalcException
     */
    public function subTime($time)
    {
        $baseTime = self::gatherTime($time);

        $baseSecs = TimeManipulator::time2seconds($baseTime);
        $resultSecs = $this->toSeconds() - $baseSecs;
        if ($resultSecs < 0) {
            throw new \Exceptions\Runtime\NegativeResultOfTimeCalcException;
        }

        return new self(TimeManipulator::seconds2time($resultSecs));
    }

    /**
     * @param DateTime|InvoiceTime|int|string|null $time
     * @return int B = 1, L = -1, E = 0
     */
    public function compare($time)
    {
        $paramSecs = TimeManipulator::time2seconds(self::gatherTime($time));
        $objSecs = $this->toSeconds();

        if ($objSecs > $paramSecs) {
            return 1;

        } elseif ($objSecs < $paramSecs) {
            return -1;

        } else {
            return 0;
        }
    }

    /**
     * @param DateTime|InvoiceTime|int|string|null $time
     * @return string
     */
    private static function gatherTime($time)
    {
        if ($time instanceof self) {
            $time = $time->getTime();
        }

        if ($time instanceof \DateTime) {
            $time = $time->format('H:i:s');
        }

        // time in seconds
        if (is_int($time) and ctype_digit((string)$time) and ($time % self::TIME_STEP) === 0) {
            $time = TimeManipulator::seconds2time($time);
        }

        // time in hours:minutes format
        if (is_string($time) and preg_match('~^\d+:[0-5][0-9]$~', $time)) {
            $time = $time . ':00'; // add SECONDS part to HH..:MM format
        }

        // time in format with comma
        if (is_string($time) and preg_match('~^\d+(,[05])?$~', $time)) {
            $time = self::timeWithComma2Time($time);
        }

        // final check
        if (!preg_match(self::TIME_REGEXP, $time)) {
            throw new InvalidArgumentException(
                'Wrong $time format.'
            );
        }

        return $time;
    }

    /**
     * @return string
     */
    public function toTimeWithComma()
    {
        if (!isset($this->timeWithComma)) {
            list($hours, $minutes, $secs) = sscanf($this->time, '%d:%d:%d');

            $this->timeWithComma = str_replace('.', ',', (string)$hours + ($minutes / 60));
        }
        return $this->timeWithComma;
    }

    /**
     * @return int
     */
    public function toSeconds()
    {
        if (!isset($this->numberOfSeconds)) {
            $this->numberOfSeconds = TimeManipulator::time2seconds($this->time);
        }

        return $this->numberOfSeconds;
    }

    /**
     * @return string
     */
    public function toHoursAndMinutes($trimLeftZero = false)
    {
        if (!isset($this->hoursAndMinutes)) {
            $this->hoursAndMinutes = substr($this->time, 0, strrpos($this->time, ':', 0));
        }

        $hoursAndMinutes = $this->hoursAndMinutes;
        if ($trimLeftZero == true) {
            $hoursAndMinutes = ltrim($hoursAndMinutes, 0);
            if ($hoursAndMinutes[0] == ':') {
                $hoursAndMinutes = '0' . $hoursAndMinutes;
            }
        }

        return $hoursAndMinutes;
    }

    /**
     * @param bool $trimLeftZero
     * @return string
     */
    public function getTime($trimLeftZero = false)
    {
        $time = $this->time;
        if ($trimLeftZero == true) {
            $time = ltrim($time, 0);
        }
        return $time;
    }

    public function __toString()
    {
        return $this->time;
    }
}