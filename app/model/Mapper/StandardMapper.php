<?php

namespace App\Model\Mapper;

/**
 * @author Jan Nedbal
 */
class StandardMapper extends \LeanMapper\DefaultMapper
{

    /** @var string */
    protected $defaultEntityNamespace = 'App\Model\Entities';

    /**
     * PK format [table]ID
     * @param string $table
     * @return string
     */
    public function getPrimaryKey($table)
    {
        return lcfirst($this->underdashToCamel($table)) . "ID";
    }

    /**
     * @param string $sourceTable
     * @param string $targetTable
     * @return string
     */
    public function getRelationshipColumn($sourceTable, $targetTable)
    {
        return $this->getPrimaryKey($this->underdashToCamel($targetTable));
    }

    /**
     * some_entity -> Model\Entity\SomeEntity
     * @param string $table
     * @param \LeanMapper\Row $row
     * @return string
     */
    public function getEntityClass($table, \LeanMapper\Row $row = null)
    {
        return $this->defaultEntityNamespace . '\\' . ucfirst($this->underdashToCamel($table));
    }

    /**
     * Model\Entity\SomeEntity -> some_entity
     * @param string $entityClass
     * @return string
     */
    public function getTable($entityClass)
    {
        return $this->camelToUnderdash($this->trimNamespace($entityClass));
    }

    /**
     * @param string $entityClass
     * @param string $field
     * @return string
     */
    public function getColumn($entityClass, $field)
    {
        return $field;
    }

    /**
     * @param string $table
     * @param string $column
     * @return string
     */
    public function getEntityField($table, $column)
    {
        return $column;
    }

    /**
     * Model\Repository\SomeEntityRepository -> some_entity
     * @param string $repositoryClass
     * @return string
     */
    public function getTableByRepositoryClass($repositoryClass)
    {
        $class = preg_replace('#([a-z0-9]+)Repository$#', '$1', $repositoryClass);

        return $this->camelToUnderdash($this->trimNamespace($class));
    }

    /**
     * camelCase -> underdash_separated.
     * @param  string
     * @return string
     */
    protected function camelToUnderdash($s)
    {
        $s = preg_replace('#(.)(?=[A-Z])#', '$1_', $s);
        $s = strtolower($s);
        $s = rawurlencode($s);

        return $s;
    }

    /**
     * underdash_separated -> camelCase
     * @param  string
     * @return string
     */
    protected function underdashToCamel($s)
    {
        //$s = strtolower($s);
        $s = preg_replace('#_(?=[a-z])#', ' ', $s);
        $s = substr(ucwords('x' . $s), 1);
        $s = str_replace(' ', '', $s);

        return $s;
    }

}
