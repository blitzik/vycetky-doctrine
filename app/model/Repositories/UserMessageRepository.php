<?php

namespace App\Model\Repositories;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\UserMessage;
use App\Model\Entities\Message;
use Nette\Utils\Validators;

class UserMessageRepository extends BaseRepository
{
    public function markMessageAsRead(Message $message, $recipientID)
    {
        Validators::assert($recipientID, 'numericint');

        if ($message->isDetached()) {
            throw new InvalidArgumentException(
                'Argument $message must be attached entity.'
            );
        }

        $this->connection->query(
            'UPDATE %n', $this->getTable(), 'SET [read] = 1
             WHERE recipient = ?', $recipientID,
            'AND messageID = ?', $message->messageID
        );
    }

    /**
     * @param array $messages
     * @throws InvalidArgumentException
     */
    public function sendMessagesToRecipients(array $messages)
    {
        $values = [];
        foreach ($messages as $message) {
            if (!$message instanceof UserMessage or
                !$message->isDetached()) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }
            $values[] = $message->getModifiedRowData();
        }

        $this->connection->query('INSERT INTO %n %ex', $this->getTable(), $values);

        $insertedID = $this->connection->getInsertId(); // first inserted ID
        foreach ($messages as $userMessage) {
            $userMessage->makeAlive($this->entityFactory, $this->connection, $this->mapper);
            $userMessage->attach($insertedID);

            ++$insertedID;
        }
    }

    /**
     * @param $messageID
     * @param $recipientID
     */
    public function removeMessage($messageID, $recipientID)
    {
        Validators::assert($messageID, 'numericint');
        Validators::assert($recipientID, 'numericint');

        $this->connection
             ->query('UPDATE %n', $this->getTable(),
                     'SET deleted = 1
                      WHERE recipient = ?', $recipientID,'
                      AND messageID = ?', $messageID);
    }

    /**
     * @param array $IDs
     * @throws \DibiException
     */
    public function removeMessages(array $IDs, $recipientID)
    {
        Validators::assert($recipientID, 'numericint');

        $this->connection->query('UPDATE %n', $this->getTable(),
                                 'SET deleted = 1
                                  WHERE messageID IN (?)', $IDs,
                                 'AND recipient = ?', $recipientID);
    }

}