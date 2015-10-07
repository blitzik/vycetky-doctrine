<?php

namespace App\Model\Authorization;

interface IResource extends \Nette\Security\IResource
{
    /**
     * Returns Resource's owner ID
     *
     * @return int
     */
    public function getOwnerId();
}