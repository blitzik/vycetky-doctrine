<?php

namespace App\Model\Domain\Attributes;

use Rhumsaa\Uuid\Uuid;

trait Identifier
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string", options={"fixed"=true})
     * @var string
     */
    private $id;



    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }



    public function __clone()
    {
        $this->id = Uuid::uuid4()->toString();
    }

}