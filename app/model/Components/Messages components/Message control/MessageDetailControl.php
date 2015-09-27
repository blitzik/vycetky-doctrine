<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\Message;
use App\Model\Facades\MessagesFacade;
use Nette\Application\UI\Control;

class MessageDetailControl extends Control
{
    /**
     * @var IMessageRecipientsControlFactory
     */
    private $recipientsControlFactory;

    /**
     * @var MessagesFacade
     */
    private $messagesFacade;

    /**
     * @var Message
     */
    private $message;

    public function __construct(
        Message $message,
        MessagesFacade $messagesFacade,
        IMessageRecipientsControlFactory $recipientsControlFactory
    ) {
        $this->message = $message;
        $this->messagesFacade = $messagesFacade;
        $this->recipientsControlFactory = $recipientsControlFactory;
    }

    protected function createComponentRecipientsList()
    {
        return $this->recipientsControlFactory->create($this->message);
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->message = $this->message;

        $template->render();
    }
}