<?php

namespace App\Model\Components;

use App\Model\Domain\Entities\IMessage;
use App\Model\Domain\Entities\SentMessage;
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
     * @var IMessage
     */
    private $message;

    public function __construct(
        IMessage $message,
        MessagesFacade $messagesFacade,
        IMessageRecipientsControlFactory $recipientsControlFactory
    ) {
        $this->message = $message->getMessage();
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