<?php

namespace App\Model\Domain\Entities;

use App\Model\Domain\IDisplayableItem;
use App\Model\Time\TimeUtils;
use Exceptions\Runtime\OtherHoursZeroTimeException;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Exceptions\Logic\InvalidArgumentException;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="listing_item",
 *      options={"collate": "utf8_czech_ci"},
 *      uniqueConstraints={
 *          @UniqueConstraint(name="listingID_day", columns={"listing_id", "day"})
 *      }
 * )
 */
class ListingItem extends Entity implements IDisplayableItem
{
    use Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="Listing")
     * @ORM\JoinColumn(name="listing_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Listing
     */
    private $listing;

    /**
     * @ORM\Column(name="day", type="smallint", nullable=false, unique=false, options={"unsigned": true})
     * @var int
     */
    protected $day;

    /**
     * @ORM\ManyToOne(targetEntity="Locality")
     * @ORM\JoinColumn(name="locality_id", referencedColumnName="id", nullable=false)
     * @var Locality
     */
    private $locality;

    /**
     * @ORM\ManyToOne(targetEntity="WorkedHours")
     * @ORM\JoinColumn(name="worked_hours_id", referencedColumnName="id", nullable=false)
     * @var WorkedHours
     */
    private $workedHours;

    /**
     * @ORM\Column(name="description", type="string", length=30, nullable=true, unique=false)
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="desc_other_hours", type="string", length=30, nullable=true, unique=false)
     * @var string
     */
    protected $descOtherHours;

    /** @var \DateTime */
    private $date;

    /**
     * @param int $day
     * @param Listing $listing
     * @param WorkedHours $workedHours
     * @param Locality $locality
     * @param string|null $description
     * @param string|null $descOtherHours
     * @return ListingItem
     */
    public function __construct(
        $day,
        Listing $listing,
        WorkedHours $workedHours,
        Locality $locality,
        $description = null,
        $descOtherHours = null
    ) {
        $this->setListing($listing);
        $this->setDay($day);
        $this->setLocality($locality);
        $this->setDescription($description);

        $this->setWorkedTime($workedHours, $descOtherHours);
    }


    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $description = $this->processString($description);
        Validators::assert($description, 'unicode:..30|null');
        $this->description = $description;
    }


    /**
     * @param string $descOtherHours
     * @param WorkedHours $workedHours
     * @throws OtherHoursZeroTimeException
     */
    public function setWorkedTime(WorkedHours $workedHours, $descOtherHours = null)
    {
        $this->setWorkedHours($workedHours);
        $this->setDescOtherHours($descOtherHours);
    }


    /**
     * @param WorkedHours $workedHours
     */
    private function setWorkedHours(WorkedHours $workedHours)
    {
        $this->workedHours = $workedHours;
    }


    /**
     * @param string|null $descOtherHours
     * @throws OtherHoursZeroTimeException
     */
    private function setDescOtherHours($descOtherHours)
    {
        $descOtherHours = $this->processString($descOtherHours);
        Validators::assert($descOtherHours, 'unicode:..30|null');

        if (isset($descOtherHours)) {
            if ($this->workedHours->otherHours->toSeconds() == 0) {
                throw new OtherHoursZeroTimeException;
            }
        }

        $this->descOtherHours = $descOtherHours;
    }


    public function removeDescOtherHours()
    {
        $this->descOtherHours = null;
    }


    /**
     * @param $day
     */
    public function setDay($day)
    {
        Validators::assert($day, 'numericint:1..31');

        $daysInMonth = $this->listing->getNumberOfDaysInMonth();
        if ($day < 1 or $day > $daysInMonth) {
            throw new InvalidArgumentException(
                'Argument $day must be integer number
                 between 1-' . $daysInMonth
            );
        }

        $this->day = $day;
    }


    /**
     * @param Listing $listing
     */
    public function setListing(Listing $listing)
    {
        $listingDaysInMonth = $listing->getNumberOfDaysInMonth();
        if (isset($this->day) and $this->day > $listingDaysInMonth) {
            throw new InvalidArgumentException(
                'Day of ListingItem exceed last day in Listing period.'
            );
        }

        $this->listing = $listing;
    }


    /**
     * @param Locality $locality
     */
    public function setLocality(Locality $locality)
    {
        $this->locality = $locality;
    }


    /**
     * @return Listing
     */
    public function getListing()
    {
        return $this->listing;
    }


    // Locality and WorkedHours are immutable objects

    /**
     * @return Locality
     */
    public function getLocality()
    {
        return $this->locality;
    }


    /**
     * @return WorkedHours
     */
    public function getWorkedHours()
    {
        return $this->workedHours;
    }


    public function compare(ListingItem $listingItem)
    {
        if ($this->day != $listingItem->day) {
            return false;
        }

        if ($this->locality->getId() != $listingItem->getLocality()->getId()) {
            return false;
        }

        if ($this->workedHours->getId() != $listingItem->getWorkedHours()->getId()) {
            return false;
        }

        return true;
    }


    /**
     * @return bool
     */
    public function areWorkedHoursWithoutLunchZero()
    {
        $workedHours = $this->workedHours
                            ->getWorkEnd()
                            ->subTime(
                                $this->workedHours
                                     ->getWorkStart()
                            );

        return ($workedHours->compare('00:00:00') === 0) ? true : false;
    }


    /*
     * -------------------------------------------
     * ----- IDisplayableItem implementation -----
     * -------------------------------------------
     */

    public function getDate()
    {
        if ($this->date === null) {
            $this->date = TimeUtils::getDateTimeFromParameters(
                $this->listing->year,
                $this->listing->month,
                $this->day
            );
        }

        return $this->date;
    }


    public function isFilling()
    {
        return false;
    }


    public function isWeekDay()
    {
        $d = date_format($this->getDate(), 'w');

        return ($d > 0 && $d < 6) ? true : false;
    }


    public function isCurrentDay()
    {
        if ($this->getDate()->format('Y-m-d') == (new \DateTime('now'))->format('Y-m-d'))
            return true;

        return false;
    }


}