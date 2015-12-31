<?php

namespace App\Model\Facades;

use App\Model\Authorization\Authorizator;
use App\Model\Domain\Entities\IMessage;
use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\User;
use App\Model\Query\ReceivedMessagesQuery;
use App\Model\Query\SentMessagesQuery;
use App\Model\Services\MessagesService;
use App\Model\Services\Readers\MessagesReader;
use App\Model\Services\Readers\UsersReader;
use App\Model\Services\Writers\MessagesWriter;
use App\Model\Subscribers\Results\NewMessageResultObject;
use Exceptions\Runtime\MessageTypeException;
use Nette\Object;

class MessagesFacade extends Object
{
    /** @var MessagesService  */
    private $messagesService;

    /** @var MessagesReader  */
    private $messagesReader;

    /** @var MessagesWriter  */
    private $messagesWriter;

    /** @var UsersReader  */
    private $usersReader;

    /** @var Authorizator  */
    private $authorizator;


    public function __construct(
        MessagesService $messagesService,
        MessagesReader $messagesReader,
        MessagesWriter $messagesWriter,
        UsersReader $usersReader,
        Authorizator $authorizator
    ) {
        $this->messagesService = $messagesService;
        $this->messagesReader = $messagesReader;
        $this->messagesWriter = $messagesWriter;
        $this->usersReader = $usersReader;
        $this->authorizator = $authorizator;
    }


    /**
     * @param SentMessagesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchSentMessages(SentMessagesQuery $query)
    {
        return $this->messagesReader->fetchMessages($query);
    }


    /**
     * @param ReceivedMessagesQuery $query
     * @return array|\Kdyby\Doctrine\ResultSet
     */
    public function fetchReceivedMessages(ReceivedMessagesQuery $query)
    {
        return $this->messagesReader->fetchMessagesReferences($query);
    }


    /**
     * @param $id
     * @param string $messageType ..Domain\Entities\IMessage::SENT or ..Domain\Entities\IMessage:RECEIVED
     * @param User|null $recipient
     * @return IMessage|null
     * @throws MessageTypeException
     */
    public function readMessage($id, $messageType, User $recipient = null)
    {
        $message = null;
        if ($messageType === IMessage::SENT) {
            $message = $this->messagesReader->getSentMessage($id);
        } elseif ($messageType === IMessage::RECEIVED) {

            $message =  $this->messagesReader->getReceivedMessage($id);
            if (isset($recipient) and $this->authorizator->isAllowed($recipient, $message, 'mark_as_read')) {
                $message->markMessageAsRead();
                $this->messagesWriter->saveMessageReference($message);
            }
        } else {
            throw new MessageTypeException;
        }

        return $message;
    }


    /**
     * @param SentMessage $message
     * @return array
     */
    public function findMessageRecipients(SentMessage $message)
    {
        $mr = $this->messagesReader->findReceivedMessages($message->getId());
        $recipients = [];
        foreach ($mr as $reference) {
            $recipients[$reference['recipient']['id']] = $reference['recipient'];
        }
        unset($mr);

        return $recipients;
    }


    public function canMessageBeSentTo(
        $recipientID,
        array $restrictedUsers,
        array $users
    ) {
        return $this->messagesService
                    ->canMessageBeSentTo($recipientID, $restrictedUsers, $users);
    }


    /**
     * @param SentMessage $message
     * @param array $recipientsIDs
     * @return NewMessageResultObject
     * @throws \Exception
     */
    public function sendMessage(SentMessage $message, array $recipientsIDs)
    {
        $recipients = $this->usersReader->findUsersByIDs($recipientsIDs);

        $messageResult = new NewMessageResultObject($message);
        $references = $this->messagesWriter->sendMessage($message, $recipients);
        $messageResult->addMessageReferences($references);

        return $messageResult;
    }


    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs)
    {
        $this->messagesWriter->removeMessages($messagesIDs);
    }


    /**
     * @param array $messagesReferencesIDs
     * @return void
     */
    public function removeMessagesReferences(array $messagesReferencesIDs)
    {
        $this->messagesWriter->removeMessagesReferences($messagesReferencesIDs);
    }
}