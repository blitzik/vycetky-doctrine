<?php

namespace App\Model\Domain\Entities;

use blitzik\Arrays\Arrays;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping as ORM;
use InvoiceTime;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="worked_hours",
 *      options={"collate": "utf8_czech_ci"},
 *      uniqueConstraints={
 *          @UniqueConstraint(name="workStart_workEnd_lunch_otherHours", columns={"work_start", "work_end", "lunch", "other_hours"})
 *      }
 * )
 */
class WorkedHours extends Entity
{
    use Identifier;
    use TInvoiceTimeConversion;

    /**
     * @ORM\Column(name="work_start", type="invoicetime", nullable=false, unique=false)
     * @var InvoiceTime
     */
    private $workStart;

    /**
     * @ORM\Column(name="work_end", type="invoicetime", nullable=false, unique=false)
     * @var InvoiceTime
     */
    private $workEnd;

    /**
     * @ORM\Column(name="lunch", type="invoicetime", nullable=false, unique=false)
     * @var InvoiceTime
     */
    private $lunch;

    /**
     * @ORM\Column(name="other_hours", type="invoicetime", nullable=true, unique=false, options={"default": "00:00:00"})
     * @var InvoiceTime
     */
    private $otherHours;

    /**
     * @var InvoiceTime
     */
    private $hours;

    /**
     * @var InvoiceTime
     */
    private $totalWorkedHours;

    /**
     * @param \DateTime|InvoiceTime|int|string|null $workStart
     * @param \DateTime|InvoiceTime|int|string|null $workEnd
     * @param \DateTime|InvoiceTime|int|string|null $lunch
     * @param \DateTime|InvoiceTime|int|string|null null $otherHours
     * @throws ShiftEndBeforeStartException
     * @throws NegativeResultOfTimeCalcException
     */
    public function __construct(
        $workStart,
        $workEnd,
        $lunch,
        $otherHours = null
    ) {
        $this->workStart = new InvoiceTime($workStart);
        $this->workEnd = new InvoiceTime($workEnd);
        $this->lunch = new InvoiceTime($lunch);
        $this->otherHours = new InvoiceTime($otherHours);

        if ($this->workStart->compare($this->workEnd) === 1) {
            throw new ShiftEndBeforeStartException(
                'You cannot quit your shift before you even started!'
            );
        }

        $this->getHours();
        $this->getTotalWorkedHours();
    }

    /**
     * @param WorkedHours|array $workedHours
     * @return bool
     */
    public function hasSameValuesAs($workedHours)
    {
        if ($workedHours instanceof self) {
            return $this->compareWithEntity($workedHours);
        } elseif (is_array($workedHours)) {
            return $this->compareWithArrayOfValues($workedHours);
        } else {
            throw new InvalidArgumentException('Only instances of ' .self::class. ' or array can pass.');
        }
    }

    /**
     * @param WorkedHours $workedHours
     * @return bool
     */
    private function compareWithEntity(WorkedHours $workedHours)
    {
        if ($this->workStart->compare($workedHours->workStart) !== 0) return false;
        if ($this->workEnd->compare($workedHours->workEnd) !== 0) return false;
        if ($this->lunch->compare($workedHours->lunch) !== 0) return false;
        if ($this->otherHours->compare($workedHours->otherHours) !== 0) return false;

        return true;
    }

    /**
     * @param array $workedHours
     * @return bool
     */
    private function compareWithArrayOfValues(array $workedHours)
    {
        $members = Arrays::pickMembers($workedHours, [
            'workStart', 'workEnd', 'lunch', 'otherHours'
        ]);

        foreach ($members as $propertyName => $value) {
            if ($this->{$propertyName}->compare((new InvoiceTime($value))) !== 0) return false;
        }

        return true;
    }

    private function caclHours()
    {
        return $this->workEnd->subTime($this->workStart)->subTime($this->lunch);
    }

    /**
     * @return InvoiceTime
     */
    public function getHours()
    {
        if (!isset($this->hours)) {
            $this->hours = $this->caclHours();
        }

        return $this->hours;
    }

    /**
     * @return InvoiceTime
     */
    public function getTotalWorkedHours()
    {
        if (!isset($this->totalWorkedHours)) {
            $this->totalWorkedHours = $this->getHours()->sumWith($this->otherHours);
        }
        return $this->totalWorkedHours;
    }

    /**
     * @return InvoiceTime
     */
    public function getWorkStart()
    {
        return $this->workStart;
    }

    /**
     * @return InvoiceTime
     */
    public function getWorkEnd()
    {
        return $this->workEnd;
    }

    /**
     * @return InvoiceTime
     */
    public function getLunch()
    {
        return $this->lunch;
    }

    /**
     * @return InvoiceTime
     */
    public function getOtherHours()
    {
        return $this->otherHours;
    }

    /**
     * @param bool $plainValues
     * @return array
     */
    public function toArray($plainValues = false)
    {
        $skippedFields = array_flip(['id', 'hours', 'totalWorkedHours']);

        $result = [];
        foreach ($this as $name => $value) {
            if (!array_key_exists($name, $skippedFields)) {
                $result[$name] = $plainValues ? $value->getTime() : $value;
            }
        }

        return $result;
    }
}