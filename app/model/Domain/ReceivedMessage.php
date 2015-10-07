<?php

namespace App\Model\Domain\Entities;

use App\Model\Authorization\IResource;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="received_message",
 *      options={"collate": "utf8_czech_ci"},
 *      indexes={
 *          @Index(name="recipient_deleted_read", columns={"recipient", "deleted", "read"}),
 *          @Index(name="message_recipient", columns={"message", "recipient"})
 *      }
 * )
 */
class ReceivedMessage extends Entity implements IMessage, IResource
{
    use Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="recipient", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $recipient;

    /**
     * @ORM\ManyToOne(targetEntity="SentMessage")
     * @ORM\JoinColumn(name="message", referencedColumnName="id", nullable=false)
     */
    private $message;

    /**
     * @ORM\Column(name="`read`", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $read = false;

    /**
     * @ORM\Column(name="deleted", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $deleted = false;

    public function __construct(
        SentMessage $message,
        User $recipient
    ) {
        $this->message = $message;
        $this->recipient = $recipient;
    }

    public function markMessageAsRead()
    {
        $this->read = true;
    }

    public function markAsDeleted()
    {
        $this->deleted = true;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function isRead()
    {
        return $this->read;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    /* ******************* */

    public function getMessage()
    {
        return $this->message;
    }

    public function isSentMessage()
    {
        return false;
    }

    public function isReceivedMessage()
    {
        return true;
    }

    /**
     * Returns Resource's owner ID
     *
     * @return int
     */
    public function getOwnerId()
    {
        return $this->recipient->getId();
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