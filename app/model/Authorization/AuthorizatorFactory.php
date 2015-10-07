<?php

namespace App\Model\Authorization;

use Nette\Object;

class AuthorizatorFactory extends Object
{
    /**
     * @return Authorizator
     */
    public function create()
    {
        return new Authorizator();
    }
}