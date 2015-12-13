<?php

namespace App\Model\Services;

use Exceptions\Runtime\InvitationExpiredException;
use App\Model\Notifications\EmailNotifier;
use App\Model\Domain\Entities\Invitation;
use Nette\Application\UI\ITemplate;
use Nette\InvalidStateException;
use Tracy\Debugger;
use Nette\Object;

class InvitationsSender extends Object
{
    /** @var EmailNotifier  */
    private $emailNotifier;

    /** @var string */
    private $systemEmail;

    /** @var string */
    private $applicationUrl;



    public function __construct(
        $systemEmail,
        $applicationUrl,
        EmailNotifier $emailNotifier
    ) {
        $this->emailNotifier = $emailNotifier;
        $this->systemEmail = $systemEmail;
        $this->applicationUrl = $applicationUrl;
    }



    /**
     * @param Invitation $invitation
     * @throws InvitationExpiredException
     * @throws InvalidStateException
     */
    public function sendInvitation(Invitation $invitation)
    {
        try {
            $this->emailNotifier->send(
                'Výčetkový systém <' . $this->systemEmail . '>',
                $invitation->email,
                function (ITemplate $template, Invitation $invitation, $senderName, $applicationUrl) {
                    $template->setFile(__DIR__ . '/../../model/Notifications/templates/invitation.latte');
                    $template->invitation = $invitation;
                    $template->username = $senderName;

                    $template->applicationUrl = $applicationUrl;

                },
                [$invitation, $invitation->getSender()->username, $this->applicationUrl]
            );
        } catch (InvalidStateException $e) {
            Debugger::log($e);
            throw $e;
        }
    }
}