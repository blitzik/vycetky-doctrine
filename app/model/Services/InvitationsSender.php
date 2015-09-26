<?php

namespace App\Model\Services;

use App\Model\Notifications\EmailNotifier;
use App\Model\Domain\Entities\Invitation;
use Exceptions\Runtime\InvitationExpiredException;
use Nette\Application\UI\ITemplate;
use Nette\InvalidStateException;
use Nette\Object;

class InvitationsSender extends Object
{
    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    /**
     * @var string
     */
    private $systemEmail;

    public function __construct(
        EmailNotifier $emailNotifier
    ) {
        $this->emailNotifier = $emailNotifier;
    }

    /**
     * @param string $systemEmail
     */
    public function setSystemEmail($systemEmail)
    {
        $this->systemEmail = $systemEmail;
    }

    /**
     * @param Invitation $invitation
     * @throws InvitationExpiredException
     * @throws InvalidStateException
     */
    public function sendInvitation(Invitation $invitation)
    {
        if (!$invitation->isActive()) {
            throw new InvitationExpiredException;
        }

        $this->emailNotifier->send(
            'Výčetkový systém <' . $this->systemEmail . '>',
            $invitation->email,
            function (ITemplate $template, Invitation $invitation, $senderName) {
                $template->setFile(__DIR__ . '/../../model/Notifications/templates/invitation.latte');
                $template->invitation = $invitation;
                $template->username = $senderName;

            },
            [$invitation, $invitation->getSender()->username]
        );
    }
}