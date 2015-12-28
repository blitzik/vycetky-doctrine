<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 27.12.2015
 */

namespace App\Model\Database\Backup\Handlers;

use App\Model\Database\Backup\DatabaseBackupFile;
use Kdyby\Monolog\Logger;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Object;

class DatabaseBackupEmailHandler extends Object implements IDatabaseBackupHandler
{
    /** @var array */
    private $receiversEmails;

    /** @var string */
    private $systemEmail;

    /** @var array */
    private $emails;

    /** @var IMailer */
    private $mailer;

    /** @var Logger */
    private $logger;


    public function __construct(
        array $receiversEmails,
        $systemEmail,
        IMailer $mailer,
        Logger $logger
    ) {
        $this->receiversEmails = $receiversEmails;
        $this->systemEmail = $systemEmail;
        $this->mailer = $mailer;
        $this->logger = $logger->channel('backupEmailHandler');
    }


    public function process(DatabaseBackupFile $file)
    {
        foreach ($this->receiversEmails as $receiverEmail) {
            $this->sendMail($receiverEmail, date('Y-m-d-H-i-s') . ' - database backup', 'OK', $file->getFilePath());
        }
    }


    /**
     * @param string $receiver
     * @param string $subject
     * @param string $messageText
     * @param string $attachedFile
     * @throws \Nette\InvalidStateException
     */
    private function sendMail($receiver, $subject, $messageText, $attachedFile = null)
    {
        $message = new Message();

        $message->setFrom('Výčetkový systém <' . $this->systemEmail . '>')
                ->addTo($receiver)
                ->setSubject($subject)
                ->setBody($messageText);

        if ($attachedFile !== null and file_exists($attachedFile)) {
            $message->addAttachment($attachedFile);
        }

        try {
            $this->mailer->send($message);
        } catch (SendException $s) {
            $this->logger->addError(sprintf('Backup file sending\'s failed. %s', $s));
        }
    }
}