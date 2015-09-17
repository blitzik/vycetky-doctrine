<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Exceptions\Logic\InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Validators;
use Nette\Utils\Random;
use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="invitation",
 *      options={"collate": "utf8_czech_ci"}
 * )
 */
class Invitation extends Entity
{
    use Identifier;
    
    /**
     * @ORM\Column(name="email", type="string", length=70, nullable=false, unique=true)
     * @var string
     */
    protected $email;

    /**
     * @ORM\Column(name="token", type="string", length=32, nullable=false, unique=false, options={"fixed": true})
     * @var string
     */
    private $token;

    /**
     * @ORM\Column(name="validity", type="datetime", nullable=false, unique=false)
     * @var DateTime
     */
    protected $validity;

    /**
     * @param string $email
     * @param DateTime $validity
     * @return Invitation
     */
    public function __construct(
        $email,
        DateTime $validity
    ) {
        $this->setEmail($email);
        $this->setValidity($validity);

        $this->generateToken();
    }

    private function generateToken()
    {
        $this->row->token = Random::generate(32);
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $email = trim($email);
        Validators::assert($email, 'email');

        $this->email = $email;
    }

    /**
     * @param DateTime $validity
     */
    public function setValidity(DateTime $validity)
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
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}