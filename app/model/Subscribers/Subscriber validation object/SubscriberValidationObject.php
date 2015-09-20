<?php

namespace App\Model\Subscribers\Validation;

use Nette\Utils\Validators;
use Nette\Object;

class SubscriberValidationObject extends Object
{
    /**
     * @var array
     */
    private $errors = [];

    public function addError($message, $type)
    {
        Validators::assert($message, 'string');
        Validators::assert($type, 'string');

        $this->errors[] = ['message' => $message, 'type' => $type];
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors);
    }

    /**
     * @return mixed
     */
    public function getFirstError()
    {
        return reset($this->errors);
    }

    /**
     * @return array
     */
    public function getAllErrors()
    {
        return $this->errors;
    }
}