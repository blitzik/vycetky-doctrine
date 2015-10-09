<?php

namespace App\Model\Components;

use App\Model\Authorization\Authorizator;
use Nette\Application\UI\Control;
use Nextras\Application\UI\SecuredLinksControlTrait;

abstract class BaseComponent extends Control
{
    use SecuredLinksControlTrait;

    /** @var Authorizator */
    protected $authorizator;

    public function setAuthorizator(Authorizator $authorizator)
    {
        $this->authorizator = $authorizator;
    }
}