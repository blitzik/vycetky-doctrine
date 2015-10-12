<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Id;
use Nette\Utils\Validators;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="locality",
 *      options={"collate": "utf8_czech_ci"},
 *      uniqueConstraints={
 *          @UniqueConstraint(name="user_name", columns={"user", "name"})
 *      }
 * )
 */
class Locality extends Entity
{
    use Identifier;

    /**
     * @ORM\Column(name="name", type="string", length=40, nullable=false, options={"collation": "utf8_bin"})
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @JoinColumn(name="user", referencedColumnName="id", nullable=false)
     * @var User
     */
    private $user;


    /**
     * @param string $localityName
     * @param User $user
     */
    public function __construct(
        $localityName,
        User $user
    ) {
        $this->setName($localityName);
        $this->user = $user;
    }

    /**
     * @param string $localityName
     */
    private function setName($localityName)
    {
        $localityName = $this->processString($localityName);

        Validators::assert($localityName, 'unicode:1..40');
        $this->name = $localityName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Locality $locality
     * @return bool
     */
    public function isSameAs(Locality $locality)
    {
        return $locality->name === $this->name;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}