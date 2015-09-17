<?php

namespace App\Model\Entities\old;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use DateTime;

/**
 * @property-read int $messageID
 * @property-read \DateTime $sent
 * @property-read string $subject
 * @property-read string $message
 * @property-read bool $deleted = false
 * @property-read bool $isReceived m:temporary
 * @property-read bool $isRead m:temporary
 * @property-read User|null $author m:hasOne(author:user)
 */
class Message extends BaseEntity
{
    const SENT = 'sent';
    const RECEIVED = 'received';

    const UNREAD = 0;
    const READ = 1;

    /**
     * @param string $subject
     * @param string $message
     * @param string $author
     * @param \DateTime $sent If $sent is null, sent is set to current date
     * @return Message
     */
    public function __construct(
        $subject,
        $message,
        $author,
        DateTime $sent = null
    ) {
        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->setSubject($subject);
        $this->setMessage($message);
        $this->setAuthor($author);

        if (is_null($sent)) {
            $sent = new DateTime();
        }

        $this->setSent($sent);
    }

    /**
     * @param string $subject
     */
    private function setSubject($subject)
    {
        $subject = trim($subject);
        Validators::assert($subject, 'unicode:1..80');
        $this->row->subject = $subject;
    }

    /**
     * @param string $message
     */
    private function setMessage($message)
    {
        $message = trim($message);
        Validators::assert($message, 'unicode:1..3000');
        $this->row->message = $message;
    }

    /**
     * @param string $author
     */
    private function setAuthor($author)
    {
        if ($author instanceof User and !$author->isDetached()) {
            $this->assignEntityToProperty($author, 'author');
        } else if (Validators::is($author, 'numericint')) {
            $this->row->author = $author;
            $this->row->cleanReferencedRowsCache('user', 'author');
        } else {
            throw new InvalidArgumentException(
                'Argument $author can by only instance of App\Entities\User or
                 integer number.'
            );
        }
    }

    /**
     * @param DateTime $sent
     */
    private function setSent(DateTime $sent)
    {
        $this->row->sent = $sent;
    }

    public function getRecipientsNames()
    {
        $this->checkEntityState();

        $recipientsNames = [];
        foreach ($this->row->referencing('user_message', 'messageID') as $userMessage) {
            $user = $userMessage->referenced('user', 'recipient');
            $recipientsNames[] = isset($user->username) ? $user->username : null;
        }

        return $recipientsNames;
    }

    /**
     * @return bool
     */
    public function isReceived()
    {
        $this->checkEntityState();
        return $this->row->isReceived == 1 ? true : false;
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        $this->checkEntityState();
        return $this->row->isRead == 1 ? true : false;
    }

    /**
     * @return bool
     */
    public function isSystemMessage()
    {
        if (isset($this->row->author) and $this->author->role == 'system') {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getMessageType()
    {
        return $this->isReceived() ? self::RECEIVED : self::SENT;
    }

}