<?php

namespace App\Model\Subscribers\Results;

use App\Model\Domain\Entities\Entity;
use Nette\Utils\Validators;

class EntityResultObject extends ResultObject
{
    /** @var array */
    protected $errors = [];

    /** @var Entity */
    protected $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function addError($message, $type)
    {
        Validators::assert($message, 'unicode');
        Validators::assert($type, 'unicode');

        $this->errors[] = ['message' => $message, 'type' => $type];
    }

    /**
     * @return bool
     */
    public function hasNoErrors()
    {
        return empty($this->errors);
    }

    /**
     * @return mixed
     */
    public function getFirstError()
    {
        return $this->errors[0];
    }

    /**
     * @return array
     */
    public function getAllErrors()
    {
        return $this->errors;
    }
}