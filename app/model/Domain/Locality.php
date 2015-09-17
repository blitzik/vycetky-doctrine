<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="locality",
 *      options={"collate": "utf8_czech_ci"}
 * )
 */
class Locality extends Entity
{
    use Identifier;

    /**
     * @ORM\Column(name="name", type="string", length=40, nullable=false, unique=true, options={"collation": "utf8_bin"})
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="User", fetch="EXTRA_LAZY", indexBy="id")
     * @ORM\JoinTable(
     *      name="locality_user",
     *      joinColumns={@JoinColumn(name="locality_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @var ArrayCollection
     */
    private $users;


    /**
     * @param string $localityName
     */
    public function __construct($localityName)
    {
        $this->setName($localityName);
        $this->users = new ArrayCollection();
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
     * @param User $user
     */
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users[$user->getId()] = $user;
        }
    }
}