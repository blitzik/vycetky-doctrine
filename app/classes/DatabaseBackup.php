<?php

class DatabaseBackup extends \Nette\Object
{
    /**
     * @var array
     */
    private $parameters = [];


    /**
     * @var MySQLDump
     */
    private $mysqlDump;

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;

        $this->mysqlDump = new MySQLDump(
            new mysqli(
                $this->parameters['host'],
                $this->parameters['username'],
                $this->parameters['password'],
                $this->parameters['dbname']
            )
        );
    }


    /**
     * @param $filePath
     * @throws Exception
     */
    public function save($filePath)
    {
        $this->mysqlDump->save($filePath);
    }

}