<?php

use Tester\Assert;

$x = require '../../bootstrap.php';

class InvoiceTimeTest extends \Tester\TestCase
{
    public function testNull()
    {
        $t = new InvoiceTime();
        Assert::same('00:00:00', $t->getTime());
        Assert::same('00:00', $t->toHoursAndMinutes());
        Assert::same('0:00', $t->toHoursAndMinutes(true));
        Assert::same('0', $t->toTimeWithComma());
        Assert::same(0, $t->toSeconds());
    }

    public function testLeadingZeroTrimming()
    {
        $t = new InvoiceTime('06:00:00');
        Assert::same('6:00', $t->toHoursAndMinutes(true));

        $t = new InvoiceTime('06:30:00');
        Assert::same('6:30', $t->toHoursAndMinutes(true));

        $t = new InvoiceTime('10:00:00');
        Assert::same('10:00', $t->toHoursAndMinutes(true));
    }

    public function testInteger()
    {
        $t = new InvoiceTime(522000); // 522 000 seconds
        Assert::same('145:00:00', $t->getTime());

        $t = new InvoiceTime(523800);
        Assert::same('145:30:00', $t->getTime());

        Assert::exception(function () {
            $t = new InvoiceTime(1600);
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');

        Assert::exception(function () {
            $t = new InvoiceTime(-522000);
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');
    }

    public function testDateTime()
    {
        $t = new InvoiceTime(new DateTime('2015-05-01 06:00:00'));
        Assert::same('06:00:00', $t->getTime());

        $t = new InvoiceTime(new DateTime('2015-05-01 06:30:00'));
        Assert::same('06:30:00', $t->getTime());

        Assert::exception(function () {
            $t = new InvoiceTime(new DateTime('2015-05-01 06:10:00'));
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');
    }

    public function testStringTime()
    {
        $t = new InvoiceTime('145:00:00');
        Assert::same('145:00:00', $t->getTime());

        $t = new InvoiceTime('145:30:00');
        Assert::same('145:30:00', $t->getTime());

        Assert::exception(function () {
            $t = new InvoiceTime('145:10:00');
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');

        Assert::exception(function () {
            $t = new InvoiceTime('-145:00:00');
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');
    }

    public function testStringHoursAndMinutes()
    {
        $t = new InvoiceTime('145:00');
        Assert::same('145:00:00', $t->getTime());

        $t = new InvoiceTime('145:30');
        Assert::same('145:30:00', $t->getTime());

        Assert::exception(function () {
            $t = new InvoiceTime('145:10');
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');

        Assert::exception(function () {
            $t = new InvoiceTime('-145:00');
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');
    }

    public function testStringHoursAndMinutesWithComma()
    {
        $t = new InvoiceTime('145');
        Assert::same('145:00:00', $t->getTime());

        $t = new InvoiceTime('145,5');
        Assert::same('145:30:00', $t->getTime());

        Assert::exception(function () {
            $t = new InvoiceTime('145,2');
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');

        Assert::exception(function () {
            $t = new InvoiceTime('-145');
        }, 'Exceptions\Logic\InvalidArgumentException', 'Wrong $time format.');
    }

    public function testTimeCompareTimes()
    {
        $t  = new InvoiceTime('145:00:00');
        $t2 = new InvoiceTime('145:00');
        $t3 = new InvoiceTime(504000); // 140:00:00
        $t4 = new InvoiceTime('150');

        Assert::same(-1, $t->compare($t4));
        Assert::same(0, $t->compare($t2));
        Assert::same(1, $t->compare($t3));
    }

    public function testSumTimes()
    {
        $t   = new InvoiceTime('145:00:00');
        $t2  = new InvoiceTime('145:00:00');

        Assert::same('290:00:00', $t->sumWith($t2)->getTime());
    }

    public function testSubTimes()
    {
        $t  = new InvoiceTime('150:00:00');
        $t2 = new InvoiceTime('145:00:00');
        $t3 = new InvoiceTime('160:00:00');

        Assert::same('05:00:00', $t->subTime($t2)->getTime());

        Assert::exception(function () use ($t, $t3) { // 150 - 160 = -10
            $r = $t->subTime($t3);
        }, '\Exceptions\Runtime\NegativeResultOfTimeCalcException');
    }

}

(new InvoiceTimeTest())->run();