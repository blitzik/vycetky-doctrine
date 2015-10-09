<?php

namespace App\Model\Subscribers\Results;

interface IResultObject
{
    /**
     * @param string $message
     * @param string $type
     * @return void
     */
    public function addError($message, $type);

    /**
     * @return bool
     */
    public function hasNoErrors();

    /**
     * @return array
     */
    public function getAllErrors();
}