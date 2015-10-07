<?php

namespace App\Model\Authorization;

interface IRole extends \Nette\Security\IRole
{
    /**
     * Returns Role's owner ID
     *
     * @return int
     */
    public function getId();
}