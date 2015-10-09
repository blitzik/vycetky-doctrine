<?php

namespace App\Model\Components;


class MessageRecipientsControl extends BaseComponent
{
    /** @var array  */
    private $recipients;

    public function __construct(
        array $recipients
    ) {
        $this->recipients = $recipients;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/template.latte');

        $template->recipients = $this->recipients;

        $template->render();
    }
}