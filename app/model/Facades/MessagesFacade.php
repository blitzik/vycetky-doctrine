<?php

namespace App\Model\Facades;

use App\Model\Repositories\UserMessageRepository;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\MessageLengthException;
use App\Model\Repositories\MessageRepository;
use App\Model\Repositories\UserRepository;
use App\Model\Entities\UserMessage;
use App\Model\Entities\Message;
use Nette\Utils\Validators;
use Nette\Security\User;
use Tracy\Debugger;

class MessagesFacade extends BaseFacade
{

    /**
     * @var UserMessageRepository
     */
    private $userMessageRepository;

    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;


    /**
     * @var \Transaction
     */
    private $transaction;

    public function __construct(
        MessageRepository $messageRepository,
        UserMessageRepository $umr,
        \Transaction $transaction,
        MessageRepository $mr,
        UserRepository $ur,
        User $user
    ) {
        parent::__construct($user);

        $this->messageRepository = $messageRepository;
        $this->userMessageRepository = $umr;
        $this->transaction = $transaction;
        $this->messageRepository = $mr;
        $this->userRepository = $ur;
    }


    /**
     * @param int $messageID
     * @param int $userID
     * @param string $type received or sent
     * @return Message
     * @throw MessageNotFoundException
     */
    public function getMessage($messageID, $userID, $type)
    {
        $message = $this->callMessageActionBasedOnType(
                $type,
                [$messageID, $userID],
                function ($messageID, $recipientID) {
                    return $this->messageRepository
                                ->getReceivedMessage($messageID, $recipientID);
                },
                function ($messageID, $authorID) {
                    return $this->messageRepository
                                ->getSentMessage($messageID, $authorID);
                }
            );

        if ($type === Message::RECEIVED and !$message->isRead()) {
            $this->userMessageRepository->markMessageAsRead($message, $userID);
        }

        return $message;
    }

    /**
     * @param string $messageType unread or read
     * @param int $offset
     * @param int $length
     * @param User|int|null $user
     * @return Message[]
     */
    public function findReceivedMessages(
        $messageType,
        $offset,
        $length,
        $user = null
    ) {
        Validators::assert($offset, 'numericint');
        Validators::assert($length, 'numericint');
        $userID = $this->getIdOfSignedInUserOnNull($user);;

        return $this->messageRepository
                    ->findReceivedMessages(
                        $userID,
                        $messageType,
                        $offset,
                        $length
                    );
    }

    /**
     * @param string $messageType unread or read
     * @param User|int|null $user
     * @return int
     */
    public function getNumberOfReceivedMessages($messageType, $user = null)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->messageRepository
                    ->getNumberOfReceivedMessages($userID, $messageType);
    }

    /**
     * @param int $offset
     * @param int $length
     * @param User|int|null $user
     * @return Message[]
     */
    public function findSentMessages($offset, $length, $user = null)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->messageRepository
                    ->findSentMessages($userID, $offset, $length);
    }

    /**
     * @param User|int|null $user
     * @return int
     */
    public function getNumberOfSentMessages($user = null)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->messageRepository->getNumberOfSentMessages($userID);
    }

    /**
     * @param string $subject
     * @param string $text
     * @param \App\Model\Entities\User|int $author
     * @param array $recipients IDs or App\Entities\User instances
     * @return Message
     * @throws MessageLengthException
     * @throws \DibiException
     */
    public function sendMessage($subject, $text, $author, array $recipients)
    {
        try {
            $this->transaction->begin();

                $message = new Message($subject, $text, $author);

                $this->messageRepository->persist($message);

                $userMessages = [];
                foreach ($recipients as $recipient) {
                    $um = new UserMessage($message, $recipient);

                    $userMessages[] = $um;
                }

                $this->userMessageRepository->sendMessagesToRecipients($userMessages);

            $this->transaction->commit();

            return $message;

        } catch (\DibiException $e) {

            $this->transaction->rollback();

            if ($e->getCode() === 1406) { // too long data for database column
                throw new MessageLengthException;
            }

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

    /**
     * @param array $messages Key => recipientID, Value = Message entity or array of messages
     * @throws InvalidArgumentException
     * @throws \DibiException
     * @return array
     */
    public function sendMessages(array $messages)
    {
        $ex = new InvalidArgumentException(
            'Only non-persisted instances of ' .Message::class. ' can pas.'
        );

        $msgs = [];
        foreach ($messages as $recipientID => $recipientMessages) {
            Validators::assert($recipientID, 'numericint');

            if (is_array($recipientMessages)) {
                foreach ($recipientMessages as $message) {
                    if (!($message instanceof Message and $message->isDetached())) {
                        throw $ex;
                    }
                    $msgs[] = $message;
                }
            } else {
                // recipientMessages contains only one message
                if (!($recipientMessages instanceof Message and $recipientMessages->isDetached())) {
                    throw $ex;
                }
                $msgs[] = $recipientMessages;
            }
        }

        try {
            $this->transaction->begin();

            $this->messageRepository->saveMessages($msgs);
            unset($msgs);

            $usersMessages = [];
            foreach ($messages as $recipientID => $recipientMessages) {
                if (is_array($recipientMessages)) {
                    foreach ($recipientMessages as $message) {
                        $recipientMessage = new UserMessage($message, $recipientID);
                        $usersMessages[] = $recipientMessage;
                    }
                } else {
                    $recipientMessage = new UserMessage($recipientMessages, $recipientID);
                    $usersMessages[] = $recipientMessage;
                }
            }

            $this->userMessageRepository->sendMessagesToRecipients($usersMessages);

            $this->transaction->commit();

            return $usersMessages;

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param int $messageID
     * @param User|int|null $user
     * @param string $type received or sent
     */
    public function removeMessage($messageID, $type, $user = null)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        $this->callMessageActionBasedOnType(
            $type,
            [$messageID, $userID],
            function ($messageID, $recipientID) {
                $this->userMessageRepository
                     ->removeMessage($messageID, $recipientID);
            },

            function ($messageID, $authorID) {
                $this->messageRepository
                     ->removeAuthorMessage($messageID, $authorID);
            }
        );
    }

    /**
     * @param array $messagesIDs
     * @param string$type
     * @param User|int|null $user
     */
    public function removeMessages(array $messagesIDs, $type, $user = null)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        $this->callMessageActionBasedOnType(
            $type,
            [$messagesIDs, $userID],
            function ($messagesIDs, $recipientID) {
                $this->userMessageRepository
                     ->removeMessages($messagesIDs, $recipientID);
            },
            function ($messagesIDs, $authorID) {
                $this->messageRepository
                     ->removeAuthorMessages($messagesIDs, $authorID);
            }
        );
    }

    /**
     * @param $type
     * @param array $args
     * @param callable $callbackForReceived
     * @param callable $callbackForSent
     * @return mixed
     */
    private function callMessageActionBasedOnType(
        $type,
        array $args,
        Callable $callbackForReceived,
        Callable $callbackForSent
    )
    {
        switch ($type) {
            case Message::RECEIVED:
                return call_user_func_array($callbackForReceived, $args);
                break;

            case Message::SENT:
                return call_user_func_array($callbackForSent, $args);
                break;

            default:
                throw new InvalidArgumentException('Argument $type has wrong value.');
        }
    }
}