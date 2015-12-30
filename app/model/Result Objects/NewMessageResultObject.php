<?php

namespace App\Model\Subscribers\Results;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\ReceivedMessage;
use Exceptions\Logic\InvalidArgumentException;

class NewMessageResultObject extends EntityResultObject
{
    /** @var ReceivedMessage[] */
    private $messageReferences;

    public function __construct(SentMessage $message)
    {
        if ($message->getId() !== null) {
            throw new InvalidArgumentException('Only new instances of ' .SentMessage::class. ' are allowed');
        }

        parent::__construct($message);
    }

    public function addMessageReferences(array $references)
    {
        $diff = [];
        foreach ($references as $recipientID => $reference) {
            $message = $reference->getMessage();
            if (!$reference instanceof ReceivedMessage or
                $message !== $this->entity) {
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

    /**
     * @return bool
     */
    public function hasNoErrors()
    {
        return $this->entity->getId() !== null and empty($this->errors);
    }
}