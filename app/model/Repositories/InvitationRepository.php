<?php

namespace App\Model\Repositories;

use Exceptions\Runtime\InvitationAlreadyExistsException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\Invitation;
use Nette\Utils\Validators;

class InvitationRepository extends BaseRepository
{
    /**
     * @param Invitation $invitation
     * @return Invitation
     * @throws InvitationAlreadyExistsException
     * @throws \DibiException
     */
    public function insertInvitation(Invitation $invitation)
    {
        if (!$invitation->isDetached()) {
            throw new InvalidArgumentException(
                'Only detached instanced of ' . Invitation::class . 'can pass.'
            );
        }

        $this->connection
             ->query('INSERT INTO invitation', $invitation->getRowData(),
                     'ON DUPLICATE KEY UPDATE
                      invitationID = LAST_INSERT_ID(invitationID)');

        // conflict of unique keys (method returns 1 for insert and 2 for update)
        if ($this->connection->getAffectedRows() == 0) {
            throw new InvitationAlreadyExistsException;
        }

        $id = $this->connection->getInsertId();
        $invitation->makeAlive($this->entityFactory, $this->connection, $this->mapper);
        $invitation->attach($id);

        return $invitation;
    }

    /**
     * @param string $email
     */
    public function removeInvitationByEmail($email)
    {
        Validators::assert($email, 'email');

        $this->connection->delete($this->getTable())
                         ->where('email = ?', $email)
                         ->execute();
    }

    /**
     * @param string $email
     * @return \App\Model\Entities\Invitation
     * @throws \Exceptions\Runtime\InvitationNotFoundException
     */
    public function getInvitation($email)
    {
        Validators::assert($email, 'email');

        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('email = ?', $email)->fetch();
        if ($result == FALSE) {
            throw new \Exceptions\Runtime\InvitationNotFoundException;
        }

        return $this->createEntity($result);
    }

}