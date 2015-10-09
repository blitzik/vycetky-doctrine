<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\IMessage;
use App\Model\Facades\MessagesFacade;

class MessageDetailControl extends BaseComponent
{
    /** @var IMessageRecipientsControlFactory  */
    private $recipientsControlFactory;

    /** @var MessagesFacade  */
    private $messagesFacade;

    /** @var IMessage  */
    private $message;

    /** @var  array */
    private $recipients;

    public function __construct(
        IMessage $message,
        MessagesFacade $messagesFacade,
        IMessageRecipientsControlFactory $recipientsControlFactory
    ) {
        $this->message = $message;
        $this->messagesFacade = $messagesFacade;
        $this->recipientsControlFactory = $recipientsControlFactory;
    }

    protected function createComponentRecipientsList()
    {
        return $this->recipientsControlFactory->create($this->recipients);
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $this->recipients = $this->messagesFacade
                                 ->findMessageRecipients($this->message->getMessage());

        $template->messageEntity = $this->message;

        $template->render();
    }

    protected function createTemplate()
    {
        $template = parent::createTemplate();
        $template->addFilter('texy', function($text) {
            $texy = new \Texy();
            $texy->setOutputMode(\Texy::HTML4_TRANSITIONAL);
            $texy->encoding = 'utf-8';
            $texy->allowedTags = array(
                'strong' => \Texy::NONE,
                'b' => \Texy::NONE,
                'a' => array('href'),
                'em' => \Texy::NONE,
                'p' => \Texy::NONE,
            );
            //$texy->allowedTags = \Texy::NONE;
            return $texy->process($text);
        });
        return $template;
    }
}