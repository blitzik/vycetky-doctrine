<?php

namespace blitzik\Arrays;

use Exceptions\Logic\MissingRequiredArrayMemberException;

class Arrays
{
    public static function count_recursive($array, $depth) {
        $count = 0;
        foreach ($array as $id => $_array) {
            if (is_array ($_array) && $depth > 0) {
                $count += self::count_recursive ($_array, $depth - 1);
            } else {
                $count += 1;
            }
        }
        return $count;
    }

    /**
     * @param array $array
     * @param array $requiredKeys
     * @return array
     * @throws MissingRequiredArrayMemberException
     */
    public static function pickMembers(array $array, array $requiredKeys)
    {
        $errMsg = '';
        $members = [];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $array)) {
                $errMsg .= $key . ', ';
            } else {
                $members[$key] = $array[$key];
            }
        }

        if (empty($errMsg)) {
            return $members;
        }

        $errMsg = substr($errMsg, 0, strlen($errMsg) - 2);
        throw new MissingRequiredArrayMemberException('Missing array members: ' . $errMsg);
    }
}