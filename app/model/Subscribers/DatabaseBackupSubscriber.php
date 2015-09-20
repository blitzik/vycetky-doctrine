<?php

namespace App\Model\Subscribers;

use App\Model\Subscribers\Validation\SubscriberValidationObject;
use Kdyby\Events\Subscriber;
use Nette\InvalidStateException;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Object;
use Tracy\Debugger;

class DatabaseBackupSubscriber extends Object implements Subscriber
{
    /**
     * @var array
     */
    private $emails = [];

    /**
     * @var
     */
    private $mailer;

    public function __construct(IMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param array $emails
     */
    public function setEmails(array $emails)
    {
        $this->emails = $emails;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'App\FrontModule\Presenters\ProfilePresenter::onDatabaseBackupSuccess'
        ];
    }

    public function onDatabaseBackupSuccess(
        $backupFilePath,
        SubscriberValidationObject $validationObject
    ) {
        $mail = new Message();
        $mail->setFrom('Výčetkový systém <' .$this->emails['system']. '>')
             ->addTo($this->emails['admin'])
             ->setSubject('Záloha databáze')
             ->addAttachment($backupFilePath);

        try {
            $this->mailer->send($mail);
        } catch (InvalidStateException $is) {
            $validationObject->addError(
                'Soubor se zálohou se nepodařilo odeslat.',
                'warning'
            );

            Debugger::log($is);
        }
    }

}