<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Exceptions\Logic\InvalidArgumentException;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;
use Nette\Utils\Random;
use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="invitation",
 *      options={"collate": "utf8_czech_ci"},
 *      indexes={@Index(name="sender_validity", columns={"sender", "validity"})}
 * )
 */
class Invitation extends Entity
{
    use Identifier;

    const TOKEN_LENGTH = 15;

    const DURATION = '+1 week';
    const NEXT_DISPATCH = '+1 day';
    
    /**
     * @ORM\Column(name="email", type="string", length=70, nullable=false, unique=true)
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="last_sending", type="date", nullable=false, unique=false)
     * @var DateTime
     */
    protected $lastSending;

    /**
     * @ORM\Column(name="token", type="string", length=15, nullable=false, unique=false, options={"fixed": true})
     * @var string
     */
    private $token;

    /**
     * @ORM\Column(name="validity", type="date", nullable=false, unique=false)
     * @var DateTime
     */
    private $validity;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="sender", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $sender;

    /**
     * @param string $email
     * @param User $sender
     */
    public function __construct(
        $email,
        User $sender
    ) {
        $this->setEmail($email);
        $this->sender = $sender;
        $this->generateToken();

        $startDate = \Nette\Utils\DateTime::createFromFormat('!Y-m-d', date('Y-m-d'));
        $this->setValidity($startDate);

        $this->lastSending = $startDate;
    }

    private function generateToken()
    {
        $this->token = Random::generate(self::TOKEN_LENGTH, '0-9a-zA-z');
    }

    private function setValidity(\DateTime $date)
    {
        $this->validity = $date->modifyClone(self::DURATION);
    }

    /**
     * @param string $email
     */
    private function setEmail($email)
    {
        Validators::assert($email, 'email');

        $this->email = $email;
    }

    public function setLastSendingTime()
    {
        $this->lastSending = new DateTime('now');
    }

    public function canBeSend()
    {
        return (new DateTime('now')) >= $this->getNextTimeOfDispatch();
    }

    /**
     * @return \DateTimeImmutable
     */
    private function getNextTimeOfDispatch()
    {
        return \DateTimeImmutable::createFromMutable($this->lastSending)
               ->modify(self::NEXT_DISPATCH);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return (new DateTime()) < $this->validity;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return DateTime
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return DateTime
     */
    public function getLastSending()
    {
        return $this->lastSending;
    }

}