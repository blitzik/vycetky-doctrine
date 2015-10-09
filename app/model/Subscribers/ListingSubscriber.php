<?php

namespace App\Model\Subscribers;

use App\Model\Domain\Entities\Listing;
use App\Model\Domain\Entities\User;
use App\Model\Facades\MessagesFacade;
use App\Model\Notifications\SharedListingNotification;
use App\Model\Subscribers\Results\IResultObject;
use Doctrine\DBAL\DBALException;
use Kdyby\Events\Subscriber;
use Nette\Object;
use Tracy\Debugger;

class ListingSubscriber extends Object implements Subscriber
{
    /** @var SharedListingNotification  */
    private $listingNotification;

    /** @var MessagesFacade  */
    private $messagesFacade;

    public function __construct(
        SharedListingNotification $listingNotification,
        MessagesFacade $messagesFacade
    ) {
        $this->listingNotification = $listingNotification;
        $this->messagesFacade = $messagesFacade;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'App\Model\Facades\ListingsFacade::onListingSharing'
        ];
    }

    public function onListingSharing(
        Listing $newListing,
        User $sender,
        IResultObject $resultObject
    ) {
        try {
            $message = $this->listingNotification
                            ->getNotificationMessage(
                                $newListing,
                                $sender
                            );
            $message->markAsSystemMessage();

            $this->messagesFacade->sendMessage($message, [$newListing->getUser()->getId()]);

        } catch (DBALException $e) {
            Debugger::log($e, Debugger::ERROR);

            $resultObject->addError(
                'Nepodařilo se odeslat zprávu o
                 dopručení sdílené výčetky.',
                'error'
            );
        }
    }
}