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
    
    /**
     * @ORM\Column(name="email", type="string", length=70, nullable=false, unique=true)
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false, unique=false)
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="token", type="string", length=15, nullable=false, unique=false, options={"fixed": true})
     * @var string
     */
    private $token;

    /**
     * @ORM\Column(name="validity", type="datetime", nullable=false, unique=false)
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
     * @param DateTime $validity
     * @param User $sender
     * @return Invitation
     */
    public function __construct(
        $email,
        DateTime $validity,
        User $sender
    ) {
        $this->setEmail($email);
        $this->setValidity($validity);
        $this->sender = $sender;
        $this->generateToken();

        $this->createdAt = new DateTime('now');
    }

    private function generateToken()
    {
        $this->token = Random::generate(15, '0-9a-zA-z');
    }

    /**
     * @param string $email
     */
    private function setEmail($email)
    {
        $email = trim($email);
        Validators::assert($email, 'email');

        $this->email = $email;
    }

    /**
     * @param DateTime $validity
     */
    private function setValidity(DateTime $validity)
    {
        if ($validity <= (new DateTime())) {
            throw new InvalidArgumentException(
                'You cannot set $validity to the past time. Check your
                 DateTime value.'
            );
        }

        $this->validity = $validity;
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
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

}