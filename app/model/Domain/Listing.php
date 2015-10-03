<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use App\Model\Time\TimeUtils;
use Nette\Utils\Validators;
use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table(
        name="listing",
 *      options={"collate": "utf8_czech_ci"},
 *      indexes={
 *          @Index(name="userID_year_month_listingID", columns={"user_id", "year", "month", "id"})
 *      }
 * )
 */
class Listing extends Entity
{
    use Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $user;
    
    /**
     * @ORM\Column(name="description", type="string", length=40, nullable=true, unique=false)
     * @var string
     */
    protected $description;
    
    /**
     * @ORM\Column(name="year", type="smallint", nullable=false, unique=false, options={"unsigned": true})
     * @var int
     */
    private $year;

    /**
     * @ORM\Column(name="month", type="smallint", nullable=false, unique=false, options={"unsigned": true})
     * @var int
     */
    private $month;
    
    /**
     * @ORM\Column(name="hourly_wage", type="smallint", nullable=true, unique=false, options={"unsigned": true})
     * @var int
     */
    protected $hourlyWage;

    /**
     * @var int
     */
    private $numberOfDaysInListingMonth;

    /**
     * @param int $year
     * @param int $month
     * @param User|int $user
     * @param string|null $description
     * @param string|null $hourlyWage
     * @return Listing
     */
    public function __construct(
        $year,
        $month,
        User $user,
        $description = null,
        $hourlyWage = null
    ) {
        Validators::assert($year, 'numericint');
        $this->year = $year;

        Validators::assert($month, 'numericint');
        $this->month = $month;

        $this->user = $user;

        $this->setDescription($description);
        $this->setHourlyWage($hourlyWage);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $description = $this->processString($description);
        Validators::assert($description, 'unicode:..40|null');

        $this->description = $description;
    }

    /**
     * @param int|null $hourlyWage
     */
    public function setHourlyWage($hourlyWage)
    {
        Validators::assert($hourlyWage, 'none|numericint:0..|null');
        if (empty($hourlyWage)) {
            $hourlyWage = null;
        }

        $this->hourlyWage = $hourlyWage;
    }

    /**
     * @return bool
     */
    public function isActual()
    {
        if ($this->getPeriod()->format('Y-m') == (new \DateTime())->format('Y-m'))
            return true;

        return false;
    }

    /**
     * @return bool|DateTime
     */
    public function getPeriod()
    {
        return TimeUtils::getDateTimeFromParameters(
            $this->year,
            $this->month
        );
    }

    /**
     * @return int
     */
    public function getNumberOfDaysInMonth()
    {
        if (!isset($this->numberOfDaysInListingMonth)) {
            $this->numberOfDaysInListingMonth = TimeUtils::getNumberOfDaysInMonth(
                $this->period->format('Y'),
                $this->period->format('n')
            );
        }

        return $this->numberOfDaysInListingMonth;
    }

    /**
     * @return string
     */
    public function entireDescription()
    {
        $desc = TimeUtils::getMonthName($this->month) . ' ' . $this->year;
        if (isset($this->description)) {
            $desc .= ' - '.$this->description;
        }/* else {
            $desc .= ' - Bez popisu';
        }*/

        return $desc;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @return int
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @return int
     */
    public function getNumberOfDaysInListingMonth()
    {
        return $this->numberOfDaysInListingMonth;
    }

}