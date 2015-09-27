<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Message;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\MessageReferencesQuery;
use Doctrine\ORM\AbstractQuery;
use Nette\Application\UI\Control;

class MessageRecipientsControl extends Control
{
    /**
     * @var Message
     */
    private $message;

    /**
     * @var MessagesFacade
     */
    private $messagesFacade;

    public function __construct(
        Message $message,
        MessagesFacade $messagesFacade
    ) {
        $this->message = $message;
        $this->messagesFacade = $messagesFacade;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->referenceMessagesRecipients = $this->messagesFacade
            ->fetchMessagesReferences(
                (new MessageReferencesQuery())
                ->includingRecipient(['id', 'username'])
                ->byMessage($this->message)
            )->toArray(AbstractQuery::HYDRATE_ARRAY);

        $template->render();
    }
}