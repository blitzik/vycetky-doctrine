<?php

namespace App\Model\Services\Writers;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Domain\Entities\ReceivedMessage;
use App\Model\Domain\Entities\User;
use Doctrine\DBAL\DBALException;
use Exceptions\Logic\InvalidArgumentException;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;
use Tracy\Debugger;

class MessagesWriter extends Object
{
    /** @var array */
    public $onError = [];

    /** @var EntityManager  */
    private $em;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->em = $entityManager;
    }


    /**
     * @param ReceivedMessage $messageReference
     * @return ReceivedMessage
     */
    public function saveMessageReference(ReceivedMessage $messageReference)
    {
        $this->em->persist($messageReference)->flush();

        return $messageReference;
    }


    /**
     * @param SentMessage $message
     * @param array $recipients
     * @return ReceivedMessage[]
     * @throws \Exception
     */
    public function sendMessage(SentMessage $message, array $recipients)
    {
        $receivedMessages = [];
        try {
            $this->em->beginTransaction();

            $this->em->persist($message);

            foreach ($recipients as $recipient) {
                if (!$recipient instanceof User) {
                    throw new InvalidArgumentException(
                        'Argument $recipients can only contains instances of ' . User::class
                    );
                }
                $m = $receivedMessages[$recipient->getId()] = new ReceivedMessage($message, $recipient);
                $this->em->persist($m);

                //if (count($receivedMessages) % 5 === 0) { // todo
                //    $this->em->flush();
                //    $this->em->clear();
                //}
            }

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->em->close();
            $this->onError('Message sending failed.', $e, self::class);

            throw $e;
        }

        return $receivedMessages;
    }


    /**
     * @param array $messagesIDs
     * @return void
     */
    public function removeMessages(array $messagesIDs)
    {
        $this->em->createQuery(
            'UPDATE ' .SentMessage::class. ' sm
             SET sm.deleted = 1
             WHERE sm.id IN(:IDs)'
        )->setParameter('IDs', $messagesIDs)
         ->execute();
    }


    /**
     * @param array $messagesReferencesIDs
     * @return void
     */
    public function removeMessagesReferences(array $messagesReferencesIDs)
    {
        $this->em->createQuery(
            'UPDATE ' .ReceivedMessage::class. ' rm
             SET rm.deleted = 1
             WHERE rm.id IN(:IDs)'
        )->setParameter('IDs', $messagesReferencesIDs)
         ->execute();
    }
}