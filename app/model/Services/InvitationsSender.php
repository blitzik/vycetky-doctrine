<?php

namespace App\Model\Services;

use Exceptions\Runtime\InvitationExpiredException;
use App\Model\Notifications\EmailNotifier;
use App\Model\Domain\Entities\Invitation;
use Nette\Application\UI\ITemplate;
use Nette\Mail\SendException;
use Tracy\Debugger;
use Nette\Object;

class InvitationsSender extends Object
{
    /** @var array */
    public $onCritical = [];

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
     * @throws SendException
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
        } catch (SendException $e) {
            $this->onCritical(sprintf('Invitation sending failed. [%s]', $invitation->getEmail()), $e, self::class);
            throw $e;
        }
    }
}