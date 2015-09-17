<?php

namespace App\Model\Components;

use Nette\InvalidArgumentException;
use Nette\Application\UI\Control;
use Nette\Security\User;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

/**
 * Class DatabaseBackupControl
 * @package App\Model\Components
 *
 * This Component and its signal is invoked by Cron exclusively
 */
class DatabaseBackupControl extends Control
{
    /**
     * @var \DatabaseBackup
     */
    private $databaseBackup;

    /**
     * @var IMailer
     */
    private $mailer;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $emails;

    /**
     * @var string
     */
    private $backupPassword;

    public function __construct(
        array $emails,
        \DatabaseBackup $databaseBackup,
        IMailer $mailer,
        User $user
    ) {
        $this->emails = $emails;

        $this->databaseBackup = $databaseBackup;
        $this->mailer = $mailer;
        $this->user = $user;
    }

    /**
     * @param $password
     */
    public function setPasswordForBackup($password)
    {
        $this->backupPassword = $password;
    }

    /**
     * @param string $errorMessage
     */
    private function logError($errorMessage)
    {
        $f = fopen(WWW_DIR . '/log/backup-failure.txt', 'a+');
        fwrite($f, PHP_EOL . date('Y-m-d H-i-s') . ' => ' . $errorMessage);
        fclose($f);
    }

    /**
     * @param string $subject
     * @param string $messageText
     * @throws \Nette\InvalidStateException
     */
    private function sendMail($subject, $messageText, $attachedFile = null)
    {
        $message = new Message();

        $message->setFrom('Výčetkový systém <' . $this->emails['system'] . '>')
                ->addTo($this->emails['admin'])
                ->setSubject($subject)
                ->setBody($messageText);

        if ($attachedFile !== null and file_exists($attachedFile)) {
            $message->addAttachment($attachedFile);
        }

        try {
            $this->mailer->send($message);
        } catch (InvalidArgumentException $is) {
            $this->logError($is->getMessage());
        }
    }

    public function handleBackup($pass)
    {
        $year = date('Y');
        $month = date('F');
        $path = WWW_DIR . "/app/backup/$year/$month/";
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true)) {
                $this->logError('mkdir() failure -> directory was not created!');
                $this->sendMail('Automatic backup', 'Check the log file.');
            }
        }

        $file = $path . 'auto-' . date('Y-m-d') . '.sql';
        if (!file_exists($file)) {
            if ($this->backupPassword !== null) {
                if ($this->backupPassword != $pass) {
                    $this->logError('Forbidden access.');
                    $this->sendMail('Automatic database backup', 'Forbidden access');
                    return;
                }
            }

            try {
                $this->databaseBackup->save($file);
                $this->sendMail(date('Y-m-d').' - Automatic database backup', 'OK', $file);

            } catch (\Exception $e) {
                $this->logError($e->getMessage());
                $this->sendMail('Automatic database backup failure', $e->getMessage());
            }
        }
    }

}