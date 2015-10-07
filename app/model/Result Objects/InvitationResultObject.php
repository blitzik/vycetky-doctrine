<?php

namespace App\Model\Subscribers\Validation;

use App\Model\Domain\Entities\Invitation;
use Nette\Utils\Validators;
use Nette\Object;

class InvitationResultObject extends Object
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var Invitation
     */
    private $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

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

    /**
     * @return Invitation
     */
    public function getInvitation()
    {
        return $this->invitation;
    }
}