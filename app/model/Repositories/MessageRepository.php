<?php

namespace App\Model\Repositories;

use Exceptions\Runtime\MessageNotFoundException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\Message;
use Nette\Utils\Validators;

class MessageRepository extends BaseRepository
{
    /**
     * @param int $messageID
     * @param int $recipientID
     * @return Message
     * @throws MessageNotFoundException
     */
    public function getReceivedMessage($messageID, $recipientID)
    {
        Validators::assert($messageID, 'numericint');
        Validators::assert($recipientID, 'numericint');

        $message = $this->connection
            ->select('m.*, [um.read] AS isRead, 1 AS isReceived')
            ->from('user_message um')
            ->innerJoin('message m ON (m.messageID = um.messageID)')
            ->where('um.recipient = ? AND um.messageID = ? AND um.deleted = 0', $recipientID, $messageID)
            ->fetch();

        if ($message === false) {
            throw new MessageNotFoundException;
        }

        return $this->createEntity($message);
    }

    /**
     * @param int $messageID
     * @param int $authorID
     * @return Message
     * @throws MessageNotFoundException
     */
    public function getSentMessage($messageID, $authorID)
    {
        Validators::assert($messageID, 'numericint');
        Validators::assert($authorID, 'numericint');

        $message = $this->connection
            ->select('*, 1 AS isRead, 0 AS isReceived')
            ->from($this->getTable())
            ->where('messageID = ? AND author = ? AND deleted = 0',
                    $messageID, $authorID)
            ->fetch();

        if ($message === false) {
            throw new MessageNotFoundException;
        }

        return $this->createEntity($message);
    }

    /**
     * @param int $userID
     * @param string $messageType
     * @param int $offset
     * @param int $length
     * @return Message[] Array of Messages or empty array
     */
    public function findReceivedMessages($userID, $messageType, $offset, $length)
    {
        Validators::assert($userID, 'numericint');
        Validators::assert($messageType, 'numericint');
        Validators::assert($offset, 'numericint');
        Validators::assert($length, 'numericint');

        $results = $this->connection
            ->select('m.messageID, m.sent, m.subject, m.author, m.deleted, 1 AS isReceived')
            ->from('user_message um FORCE INDEX(recipient_read_deleted_messageID)')
            ->innerJoin('message m ON (m.messageID = um.messageID)')
            ->where('um.recipient = ? AND [um.read] = ? AND um.deleted = 0', $userID, $messageType)
            ->offset($offset)
            ->limit($length)
            ->orderBy('um.messageID DESC')->fetchAll();

        return $this->createEntities($results);
    }

    /**
     * @param int $userID
     * @param string $messageType
     * @return int
     */
    public function getNumberOfReceivedMessages($userID, $messageType)
    {
        Validators::assert($userID, 'numericint');
        Validators::assert($messageType, 'numericint');

        $result = $this->connection
            ->select('COUNT(userMessageID) AS count')
            ->from('user_message')
            ->where('recipient = ? AND [read] = ? AND deleted = 0', $userID, $messageType)
            ->groupBy('recipient')
            ->fetch();

        return $result['count'];
    }

    /**
     * @param int $userID
     * @param int $offset
     * @param int $length
     * @return Message[] Array of Messages or empty array
     */
    public function findSentMessages($userID, $offset, $length)
    {
        Validators::assert($userID, 'numericint');
        Validators::assert($offset, 'numericint');
        Validators::assert($length, 'numericint');

        $result = $this->connection
            ->select('messageID, sent, subject, author, deleted, 0 AS isReceived')
            ->from($this->getTable() . ' FORCE INDEX(author_deleted_messageID)')
            ->where('author = ? AND deleted = 0', $userID)
            ->orderBy('messageID DESC')
            ->offset($offset)
            ->limit($length)
            ->fetchAll();

        return $this->createEntities($result);
    }

    /**
     * @param int $userID
     * @return int
     */
    public function getNumberOfSentMessages($userID)
    {
        Validators::assert($userID, 'numericint');

        $result = $this->connection
            ->select('COUNT(messageID) AS count')
            ->from($this->getTable())
            ->where('author = ? AND deleted = 0', $userID)
            ->groupBy('author')
            ->fetch();

        return $result['count'];
    }

    /**
     * @param $messageID
     * @param $authorID
     * @return void
     */
    public function removeAuthorMessage($messageID, $authorID)
    {
        Validators::assert($messageID, 'numericint');
        Validators::assert($authorID, 'numericint');

        $this->connection
             ->query('UPDATE %n', $this->getTable(), '
                      SET deleted = 1
                      WHERE messageID = ?', $messageID,
                     'AND author = ?', $authorID);
    }

    /**
     * @param array $messagesIDs
     * @param $authorID
     */
    public function removeAuthorMessages(array $messagesIDs, $authorID)
    {
        Validators::assert($authorID, 'numericint');

        $this->connection
             ->query('UPDATE %n', $this->getTable(),
                     'SET deleted = 1
                      WHERE messageID IN (?)', $messagesIDs,
                     'AND author = ?', $authorID);
    }

    /**
     * @param array $messages
     * @throws \DibiException
     */
    public function saveMessages(array $messages)
    {
        $values = [];
        foreach ($messages as $message) {
            if (!$message instanceof Message or
                !$message->isDetached()) {
                throw new InvalidArgumentException(
                    'Only detached instances of Message can pass'
                );
            }
            $message->excludeTemporaryFields();

            $values[] = $message->getModifiedRowData();
        }

        $this->connection->query('INSERT INTO %n %ex', $this->getTable(), $values);

        $insertedID = $this->connection->getInsertId(); // first inserted ID
        foreach ($messages as $message) {
            $message->makeAlive($this->entityFactory, $this->connection, $this->mapper);
            $message->attach($insertedID);

            $insertedID++;
        }
    }
}