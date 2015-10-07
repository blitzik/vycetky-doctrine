<?php

namespace App\Model\Subscribers\Validation;

use App\Model\Domain\Entities\Invitation;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\ReceivedMessage;
use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use Nette\Object;

class NewMessageResultObject extends Object
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var Invitation
     */
    private $message;

    /**
     * @var ReceivedMessage[]
     */
    private $messageReferences;

    public function __construct(SentMessage $message)
    {
        if ($message->getId() !== null) {
            throw new InvalidArgumentException('Only new instances of ' .SentMessage::class. ' are allowed');
        }

        $this->message = $message;
    }

    public function addMessageReferences(array $references)
    {
        $diff = [];
        foreach ($references as $recipientID => $reference) {
            $message = $reference->getMessage();
            if (!$reference instanceof ReceivedMessage or
                $message !== $this->message) {
                $diff[] = $reference->getId();
            }
        }

        if (count($diff) > 0) {
            throw new InvalidArgumentException(
                'Argument $references contains MessageReferences that does not belong
                 to given SentMessage. (IDs: ' .implode(', ', $diff). ')'
            );
        }

        $this->messageReferences = $references;
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
        return $this->message->getId() !== null and empty($this->errors);
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
    public function getMessage()
    {
        return $this->message;
    }
}