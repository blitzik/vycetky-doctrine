<?php

namespace App\Model\Entities\old;

use Exceptions\Runtime\DetachedEntityInstanceException;
use Exceptions\Logic\InvalidArgumentException;
use LeanMapper\Entity;

abstract class BaseEntity extends Entity
{
    private function loadState($args = null)
    {
        parent::__construct($args);
    }

    /**
     * @throws DetachedEntityInstanceException
     */
    public function checkEntityState()
    {
        if ($this->isDetached()) {
            throw new DetachedEntityInstanceException;
        }
    }

    /**
     * Umožňuje předat entitě místo navázaných entit jejich 'id'
     * @Author Shaman
     *
     * @param string $name
     * @param mixed $value
     * @throws \LeanMapper\Exception\InvalidMethodCallException
     * @throws \LeanMapper\Exception\MemberAccessException
     */
    public function __set($name, $value)
    {
        $property = $this->getCurrentReflection()->getEntityProperty($name);
        //dump($name);
        //dump($value);
        //dump($property);
        if ($property->hasRelationship() && !($value instanceof Entity)) {
            $relationship = $property->getRelationship();
            $this->row->{$property->getColumn()} = $value;
            $this->row->cleanReferencedRowsCache(
                $relationship->getTargetTable(),
                $relationship->getColumnReferencingTargetTable()
            );
        } else {
            parent::__set($name, $value);
        }
    }

    public function excludeTemporaryFields()
    {
        $properties = $this->getCurrentReflection()->getEntityProperties();
        foreach ($properties as $name => $property) {
            if ($property->hasCustomFlag('temporary')) {
                unset($this->row->{$name});
            }
        }
    }

    /**
     * @param Entity $entity
     * @param array $excludedFields
     * @return bool
     */
    public function compare(Entity $entity, array $excludedFields = null)
    {
        if (!$entity instanceof $this) {
            throw new InvalidArgumentException(
                'Argument $entity has wrong instance type.'
            );
        }

        $_this = $this->getRowData();
        $e = $entity->getRowData();

        if (isset($excludedFields)) {
            $excludedFields = array_flip($excludedFields);
            foreach ($excludedFields as $fieldName => $v) {
                if (array_key_exists($fieldName, $_this)) {
                    unset($_this[$fieldName]);
                }
                if (array_key_exists($fieldName, $e)) {
                    unset($e[$fieldName]);
                }
            }
        }

        return md5(json_encode($_this)) === md5(json_encode($e));
    }

    /**
     * When cloning some Entity that is NOT in detached mode,
     * the cloning will create new Entity with the same properties
     * but without ID. If the ID should be preserved, just invoke
     * ->detach on existing attached entity before its cloning.
     *
     * ATTENTION: The mapper of Entity is gonna be removed, so
     *            you cannot get reference to another Entity within
     *            cloned Entity. But the IDs of those Entities are
     *            available inside the $row.
     */
    public function __clone()
    {
        if (!$this->row->isDetached()) {
            $this->row = clone $this->row;

            $primaryKey = $this->mapper
                               ->getPrimaryKey(
                                   $this->getReflection()
                                        ->getShortName()
                               );
            $this->row->detach();
            // Entity now has NO ID, but for persisting is
            // used $modified property of Result, where the ID
            // is still available -> Error when unique key is defined
            // at column/s.

            // we have to unset the ID manually
            unset($this->row->{$primaryKey});
        }

        $this->mapper = null;
    }

}