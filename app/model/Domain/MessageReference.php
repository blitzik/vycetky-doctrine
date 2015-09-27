<?php

namespace App\Model\Domain\Entities;

use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="user_message",
 *      options={"collate": "utf8_czech_ci"},
 *      uniqueConstraints={
 *          @UniqueConstraint(name="recipient_message", columns={"recipient", "message"})
 *      }
 * )
 */
class MessageReference extends Entity
{
    use Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="recipient", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $recipient;

    /**
     * @ORM\ManyToOne(targetEntity="Message")
     * @ORM\JoinColumn(name="message", referencedColumnName="id", nullable=false)
     */
    private $message;

    /**
     * @ORM\Column(name="`read`", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $read = false;

    public function __construct(
        Message $message,
        User $recipient
    ) {
        $this->message = $message;
        $this->recipient = $recipient;
    }

    public function setMessageAsRead()
    {
        $this->read = true;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function isRead()
    {
        return $this->read;
    }
}