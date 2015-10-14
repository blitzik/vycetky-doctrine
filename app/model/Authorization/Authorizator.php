<?php

namespace App\Model\Authorization;

use Nette\Security\IAuthorizator;
use Nette\Security\Permission;
use Nette\Object;

class Authorizator extends Object implements IAuthorizator
{
    /** @var Permission  */
    private $authorizator;

    public function __construct()
    {
        $this->authorizator = new Permission();

        $this->defineRoles($this->authorizator);
        $this->defineResources($this->authorizator);
        $this->defineRelationships($this->authorizator);
    }

    public function isAllowed(
        $role = Permission::ALL,
        $resource = Permission::ALL,
        $privilege = Permission::ALL
    ) {
        return $this->authorizator->isAllowed($role, $resource, $privilege);
    }

    private function defineRoles(Permission $authorizator)
    {
        $authorizator->addRole('employee');
        $authorizator->addRole('admin');
    }



    private function defineResources(Permission $authorizator)
    {
        $authorizator->addResource('listing');
        $authorizator->addResource('message');

        $authorizator->addResource('relationships_tables');
        $authorizator->addResource('users_overview');

        $authorizator->addResource('database_backup');
    }



    private function defineRelationships(Permission $authorizator)
    {
        $authorizator->allow('employee', 'listing', Permission::ALL, [$this, 'isOwner']);
        $authorizator->allow('employee', 'message', ['send', 'remove', 'view', 'mark_as_read'], [$this, 'isOwner']);

        $authorizator->allow('admin', null, Permission::ALL);
        $authorizator->deny('admin', 'message', 'mark_as_read', [$this, 'isNotOwner']);
    }



    public function isOwner(Permission $authorizator)
    {
        if (!($authorizator->queriedRole instanceof IRole))  {
            throw new \Exception('The Role\'s owner has to implement IRole');
        }

        if ($authorizator->queriedResource instanceof IResource) {
            return $authorizator->queriedResource->getOwnerId() === Permission::ALL || // unrestricted owner
                   $authorizator->queriedRole->getId() === $authorizator->queriedResource->getOwnerId();

        } else {
            return TRUE; //  static resource is always unrestricted
        }
    }


    public function isNotOwner(Permission $authorizator)
    {
        if (!($authorizator->queriedRole instanceof IRole))  {
            throw new \Exception('The Role\'s owner has to implement IRole');
        }

        if ($authorizator->queriedResource instanceof IResource) {
            return $authorizator->queriedRole->getId() !== $authorizator->queriedResource->getOwnerId();

        } else {
            return TRUE; //  static resource is always unrestricted
        }
    }
}