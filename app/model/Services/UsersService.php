<?php

namespace App\Model\Services;

use Nette\Object;

class UsersService extends Object
{
    /**
     * @param array $users
     * @return array
     */
    public function separateSuspendedUsers(array $users)
    {
        $resultArray = [];
        $resultArray['suspendedUsers'] = [];
        $resultArray['activeUsers'] = [];

        foreach ($users as $user) {
            if ($user['isClosed'] === true) {
                $resultArray['suspendedUsers'][$user['id']] = $user;
            } else {
                $resultArray['activeUsers'][$user['id']] = $user;
            }
            unset($resultArray[$user['id']]);
        }

        return $resultArray;
    }
}