<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 30.12.2015
 */

namespace App\Model\Subscribers;

use App\Model\Services\InvitationHandler;
use App\Model\Services\InvitationsSender;
use App\Model\Services\Managers\ListingsManager;
use App\Model\Services\Readers\InvitationsReader;
use App\Model\Services\Writers\ListingItemsWriter;
use App\Model\Services\Writers\ListingsWriter;
use App\Model\Services\Writers\MessagesWriter;
use App\Model\Services\Writers\UsersWriter;
use Kdyby\Events\Subscriber;
use Kdyby\Monolog\Logger;
use Nette\Object;
use Nette\Utils\Strings;

class MonologSubscriber extends Object implements Subscriber
{
    /** @var Logger */
    private $logger;


    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            // INFO
            InvitationsReader::class . '::onInfo',

            // ERROR
            ListingsManager::class . '::onError',
            MessagesWriter::class . '::onError',
            UsersWriter::class . '::onError',

            // CRITICAL
            InvitationSubscriber::class . '::onCritical',
            InvitationHandler::class . '::onCritical',
            InvitationsSender::class . '::onCritical',
            ListingsWriter::class . '::onCritical',
            ListingItemsWriter::class . '::onCritical',
        ];
    }


    /**
     * @param string $message
     * @param \Exception $ex
     * @param string $channel
     */
    public function onInfo($message, \Exception $ex = null, $channel = null)
    {
        $l = $this->getLogger($channel);

        $l->addInfo(
            sprintf('%s | ERR_MSG: %s', $message, isset($ex) ? $ex->getMessage() : '-')
        );
    }


    /**
     * @param string $message
     * @param \Exception $ex
     * @param string $channel
     */
    public function onError($message, \Exception $ex = null, $channel = null)
    {
        $l = $this->getLogger($channel);

        $l->addError(
            sprintf('%s | ERR_MSG: %s', $message, isset($ex) ? $ex->getMessage() : '-')
        );
    }


    /**
     * @param string $message
     * @param \Exception $ex
     * @param string $channel
     */
    public function onCritical($message, \Exception $ex = null, $channel = null)
    {
        $l = $this->getLogger($channel);

        $l->addCritical(
            sprintf('%s | ERR_MSG: %s', $message, isset($ex) ? $ex->getMessage() : '-')
        );
    }


    private function getLogger($channel)
    {
        $l = $this->logger;
        if (isset($channel)) {
            $l = $l->channel(Strings::webalize($channel));
        }

        return $l;
    }
}