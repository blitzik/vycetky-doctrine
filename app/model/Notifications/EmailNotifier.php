<?php

namespace App\Model\Notifications;

use Nette\Application\UI\ITemplateFactory;
use Nette\Application\LinkGenerator;
use Nette\InvalidStateException;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Object;

class EmailNotifier extends Object
{
    /**
     * @var IMailer
     */
    private $mailer;

    /**
     * @var LinkGenerator
     */
    private $linkGenerator;

    /**
     * @var ITemplateFactory
     */
    private $templateFactory;


    public function __construct(
        IMailer $mailer,
        LinkGenerator $linkGenerator,
        ITemplateFactory $templateFactory
    ) {
        $this->mailer = $mailer;
        $this->linkGenerator = $linkGenerator;
        $this->templateFactory = $templateFactory;
    }

    /**
     * @param string $senderEmail
     * @param string $recipientEmail
     * @param callable $templateCallback
     * @param array $param
     * @throws InvalidStateException
     */
    public function send(
        $senderEmail,
        $recipientEmail,
        Callable $templateCallback,
        array $param = []
    ) {

        $template = $this->prepareTemplate($templateCallback, $param);

        $mail = new Message();
        $mail->setFrom($senderEmail)
             ->addTo($recipientEmail)
             ->setHtmlBody($template);

        $this->mailer->send($mail);
    }

    /**
     * @param callable $templateCallback
     * @param array $param
     * @return \Nette\Application\UI\ITemplate
     */
    private function prepareTemplate(
        Callable $templateCallback,
        array $param = []
    ) {
        $template = $this->templateFactory->createTemplate();
        array_unshift($param, $template);

        call_user_func_array($templateCallback, $param);

        $template->link = $this->linkGenerator;

        return $template;
    }
}