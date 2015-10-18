<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\MagicAccessors;

abstract class Entity
{
    use MagicAccessors;

    /**
     * Returns NULL if empty string is given
     *
     * @param $string
     * @return null|string
     */
    protected function processString($string)
    {
        $string = trim($string);
        if ($string === '') {
            $string = null;
        }

        return $string;
    }
}