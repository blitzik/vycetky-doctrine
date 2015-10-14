<?php

namespace App\Model\Domain\Entities;

use App\Model\Authorization\IResource;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="sent_message",
 *      options={"collate": "utf8_czech_ci"},
 *      indexes={
 *          @Index(name="author_deleted_system", columns={"author", "deleted", "is_system_message"})
 *      }
 * )
 */
class SentMessage extends Entity implements IMessage, IResource
{
    use Identifier;

    /**
     * @ORM\Column(name="sent", type="datetime", nullable=false, unique=false)
     * @var \DateTime
     */
    private $sent;

    /**
     * @ORM\Column(name="subject", type="string", length=80, nullable=false, unique=false)
     * @var string
     */
    private $subject;

    /**
     * @ORM\Column(name="text", type="string", length=3000, nullable=false, unique=false)
     * @var string
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="author", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $author;
    
    /**
     * @ORM\Column(name="author_name", type="string", length=25, nullable=false, unique=false)
     * @var string
     */
    private $authorName;
    
    /**
     * @ORM\Column(name="author_role", type="string", length=20, nullable=false, unique=false)
     * @var string
     */
    private $authorRole;
    
    /**
     * @ORM\Column(name="deleted", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $deleted = false;

    /**
     * @ORM\Column(name="is_system_message", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $isSystemMessage = false;

    /**
     * @ORM\Column(name="sent_by_author_role", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $sentByAuthorRole = false;

    public function __construct(
        $subject,
        $text,
        User $author
    ) {
        $this->setSubject($subject);
        $this->setText($text);

        $this->author = $author;
        $this->authorName = $author->username;
        $this->authorRole = $this->author->getRoleId();
        $this->sent = new \DateTime('now');
    }

    /**
     * @param string $subject
     */
    private function setSubject($subject)
    {
        $subject = $this->processString($subject);
        Validators::assert($subject, 'unicode:..80');

        $this->subject = $subject;
    }

    /**
     * @param string $text
     */
    private function setText($text)
    {
        $text = $this->processString($text);
        Validators::assert($text, 'unicode:..3000');

        $this->text = $text;
    }

    public function markAsDeleted()
    {
        $this->deleted = true;
    }

    public function markAsSystemMessage()
    {
        $this->isSystemMessage = true;
    }

    public function sendByAuthorRole()
    {
        $this->sentByAuthorRole = true;
    }

    /**
     * @return \DateTime
     */
    public function getSent()
    {
        return clone $this->sent;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @return string
     */
    public function getAuthorRole()
    {
        return $this->authorRole;
    }

    /**
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    public function isSystemMessage()
    {
        return $this->isSystemMessage;
    }

    public function isSentByAuthorRole()
    {
        return $this->sentByAuthorRole;
    }

    /* ************************* */

    public function getMessage()
    {
        return $this;
    }

    public function isSentMessage()
    {
        return true;
    }

    public function isReceivedMessage()
    {
        return false;
    }

    /**
     * Returns Resource's owner ID
     *
     * @return int
     */
    public function getOwnerId()
    {
        return $this->author->getId();
    }

    /**
     * Returns a string identifier of the Resource.
     * @return string
     */
    function getResourceId()
    {
        return 'message';
    }


}