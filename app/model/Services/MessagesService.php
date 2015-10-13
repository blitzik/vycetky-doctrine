<?php

namespace App\Model\Services;

use Nette\Object;

class MessagesService extends Object
{
    public function canMessageBeSentTo(
        $recipientID,
        array $restrictedUsers,
        array $users
    ) {
        $isBlockedByMe = array_key_exists($recipientID, $restrictedUsers['usersBlockedByMe']);
        $isBlockingMe = array_key_exists($recipientID, $restrictedUsers['usersBlockingMe']);
        $isSuspended = array_key_exists($recipientID, $users['suspendedUsers']);
        $isActive = array_key_exists($recipientID, $users['activeUsers']); // not suspended

        if (!$isBlockedByMe and !$isBlockingMe and !$isSuspended and $isActive) {
            return true;
        }

        return false;
    }
}