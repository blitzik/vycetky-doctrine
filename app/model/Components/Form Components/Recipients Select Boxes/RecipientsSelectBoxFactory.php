<?php

namespace App\Forms\Fields;

use App\Model\Authorization\Authorizator;
use App\Model\Domain\Entities\User;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\SelectBox;
use Nette\Utils\Arrays;

class RecipientsSelectBoxFactory
{
    /** @var Authorizator  */
    private $authorizator;

    public function __construct(Authorizator $authorizator)
    {
        $this->authorizator = $authorizator;
    }

    /**
     * @param User $sender
     * @param array $usersByRestrictions
     * @param bool $multi
     * @return MultiSelectBox|SelectBox
     */
    public function create(User $sender, array $usersByRestrictions, $multi = false)
    {
        $recipients = $this->prepareRecipients($sender, $usersByRestrictions);

        $selectBox = null;
        if ($multi === true) {
            $selectBox = new MultiSelectBox('Příjemci', $recipients);
        } else {
            $selectBox = new SelectBox('Příjemce', $recipients);
        }

        $selectBox->setAttribute('size', 13);
        $selectBox->setRequired('Vyberte příjemce.');

        return $selectBox;
    }

    /**
     * @param User $sender
     * @param array $usersByRestrictions
     * @return array
     */
    private function prepareRecipients(User $sender, array $usersByRestrictions)
    {
        if (empty($usersByRestrictions)) {
            return [];
        }

        unset(
            $usersByRestrictions['suspendedUsers'][$sender->getId()],
            $usersByRestrictions['activeUsers'][$sender->getId()]
        );

        if (!$this->authorizator->isAllowed($sender, 'message', 'send_to_restricted_recipients')) {
            $recipients = array_diff_key(
                $usersByRestrictions['activeUsers'],
                $usersByRestrictions['suspendedUsers'],
                $usersByRestrictions['usersBlockedByMe'],
                $usersByRestrictions['usersBlockingMe']
            );
        } else {
            $recipients = $usersByRestrictions['activeUsers'] +
                //$usersByRestrictions['suspendedUsers'] +  // do not show suspended users to admin
                $usersByRestrictions['usersBlockedByMe'] +
                $usersByRestrictions['usersBlockingMe'];
        }

        return Arrays::associate($recipients, 'id=username');
    }
}