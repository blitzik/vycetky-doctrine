<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\SentMessage;
use App\Model\Facades\MessagesFacade;
use App\Model\Query\ReceivedMessagesQuery;
use Doctrine\ORM\AbstractQuery;
use Nette\Application\UI\Control;

class MessageRecipientsControl extends Control
{
    /**
     * @var SentMessage
     */
    private $message;

    /**
     * @var MessagesFacade
     */
    private $messagesFacade;

    public function __construct(
        SentMessage $message,
        MessagesFacade $messagesFacade
    ) {
        $this->message = $message;
        $this->messagesFacade = $messagesFacade;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->recipients = $this->messagesFacade->findRecipients($this->message);

        $template->render();
    }
}