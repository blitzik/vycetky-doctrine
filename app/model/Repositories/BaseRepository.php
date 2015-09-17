<?php

namespace App\Model\Repositories;

use App\Model\Entities\BaseEntity;
use LeanMapper\Repository;
use Nette\Utils\Arrays;
use LeanMapper\Entity;

abstract class BaseRepository extends Repository
{
    protected function initEvents()
    {
        parent::initEvents();

        $this->onBeforePersist[] = function (BaseEntity $entity) {
            $entity->excludeTemporaryFields();
        };
    }

    /**
     * @param Entity $entity
     * @return int
     * @throws \DibiException
     */
    protected function insertIntoDatabase(Entity $entity)
    {
        $primaryKey = $this->mapper->getPrimaryKey($this->getTable());
        $values = $entity->getModifiedRowData();

        $this->changeEmptyStringsToNull(
            $values,
            $entity->getReflection()->getEntityProperties()
        );

        $this->connection->query(
            'INSERT INTO %n %v', $this->getTable(), $values
        );
        return isset($values[$primaryKey]) ? $values[$primaryKey] : $this->connection->getInsertId();
    }

    /**
     * @param Entity $entity
     * @return \DibiResult|int
     */
    protected function updateInDatabase(Entity $entity)
    {
        $primaryKey = $this->mapper->getPrimaryKey($this->getTable());
        $idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);
        $values = $entity->getModifiedRowData();

        $this->changeEmptyStringsToNull(
            $values,
            $entity->getReflection()->getEntityProperties()
        );

        return $this->connection->query(
            'UPDATE %n SET %a WHERE %n = ?', $this->getTable(), $values, $primaryKey, $entity->{$idField}
        );
    }

    /**
     * @param array $values
     * @param array $entityProperties
     */
    protected function changeEmptyStringsToNull(array & $values, array $entityProperties = [])
    {
        foreach ($entityProperties as $property) {
            if ($property->getType() == 'string' and $property->isNullable()) {
                Arrays::renameKey($values, $property->getName(), $property->getName().'%sN');
            }
        }
    }
}