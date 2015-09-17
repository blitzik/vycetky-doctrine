<?php

namespace App\Model\Repositories;

use Exceptions\Runtime\UserNotFoundException;
use App\Model\Entities\User;
use Nette\Utils\Validators;

class UserRepository extends BaseRepository
{
    /**
     * @param array $exceptUsers
     * @return array Array of user names
     */
    public function findAllUsers(array $exceptUsers = null)
    {
        $result = $this->connection->select('userID, username')
                                    ->from($this->getTable())
                                    ->orderBy('username ASC')
                                    ->fetchPairs('userID', 'username');

        unset($result[0]); // system
        if (isset($exceptUsers) or !empty($exceptUsers)) {
            foreach (array_flip($exceptUsers) as $id => $val) {
                if (array_key_exists($id, $result)) {
                    unset($result[$id]);
                }
            }
        }

        return $result;
    }


    /**
     * @param $userID
     * @return User
     * @throw UserNotFoundException
     */
    public function getUserByID($userID)
    {
        Validators::assert($userID, 'numericint');

        $user =  $this->connection->select('*')
                                  ->from($this->getTable())
                                  ->where('userID = ?', $userID)
                                  ->fetch();

        if ($user == false){
            throw new UserNotFoundException;
        }

        return $this->createEntity($user);
    }

    /**
     *
     * @param string $email
     * @return \App\Model\Entities\User
     * @throws \Exceptions\Runtime\UserNotFoundException
     */
    public function findByEmail($email)
    {
        Validators::assert($email, 'email');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('email = ?', $email)
                                   ->fetch();

        if ($result == FALSE) {
            throw new \Exceptions\Runtime\UserNotFoundException;
        }

        return $this->createEntity($result);
    }

    /**
     *
     * @param string $username
     * @return \App\Model\Entities\User
     * @throws \Exceptions\Runtime\UserNotFoundException
     */
    public function findByUsername($username)
    {
        Validators::assert($username, 'unicode');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('username = ?', $username)
                                   ->fetch();

        if ($result == FALSE) {
            throw new \Exceptions\Runtime\UserNotFoundException;
        }

        return $this->createEntity($result);
    }

    /**
     *
     * @param string $username
     * @return boolean
     * @throws \Exceptions\Runtime\UserAlreadyExistsException
     */
    public function checkUsername($username)
    {
        Validators::assert($username, 'unicode');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('username = ?', $username)
                                   ->fetch();
        if ($result != FALSE)
            throw new \Exceptions\Runtime\UserAlreadyExistsException;

        return FALSE;
    }

    /**
     *
     * @param string $email
     * @return boolean
     * @throws \Exceptions\Runtime\UserAlreadyExistsException
     */
    public function checkEmail($email)
    {
        Validators::assert($email, 'email');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('email = ?', $email)
                                   ->fetch();
        if ($result != FALSE)
            throw new \Exceptions\Runtime\UserAlreadyExistsException;

        return FALSE;
    }

}