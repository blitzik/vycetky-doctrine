<?php

namespace App\Model\Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="message",
 *      options={"collate": "utf8_czech_ci"},
 *      indexes={
 *          @Index(name="author_deleted", columns={"author", "deleted"})
 *      }
 * )
 */
class Message extends Entity
{
    use Identifier;

    const SENT     = 'sent';
    const RECEIVED = 'received';
    const READ     = 'read';
    const UNREAD   = 'unread';

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
     * @ORM\Column(name="deleted", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $deleted = false;

    /**
     * @ORM\Column(name="is_system_message", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $isSystemMessage = false;

    public function __construct(
        $subject,
        $text,
        User $author,
        $isSystemMessage = false
    ) {
        $this->setSubject($subject);
        $this->setText($text);

        $this->author = $author;
        $this->sent = new \DateTime('now');

        $this->isSystemMessage = $isSystemMessage;
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

    public function setAsDeleted()
    {
        $this->deleted = true;
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
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
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

}