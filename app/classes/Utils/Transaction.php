<?php

class Transaction
{
    /**
     *
     * @var \LeanMapper\Connection
     */
    private $connection;

    public function __construct(\LeanMapper\Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     *
     * @param string|null $savepoint
     */
    public function begin($savepoint = NULL)
    {
        $this->connection->begin($savepoint);
    }

    /**
     *
     * @param string|null $savepoint
     */
    public function commit($savepoint = NULL)
    {
        $this->connection->commit($savepoint);
    }

    /**
     *
     * @param string|null $savepoint
     */
    public function rollback($savepoint = NULL)
    {
        $this->connection->rollback($savepoint);
    }
}