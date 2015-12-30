<?php

namespace App\Model\Database\Backup;

use App\Model\Database\Backup\Handlers\IDatabaseBackupHandler;
use App\Model\Subscribers\Results\ResultObject;
use Kdyby\Monolog\Logger;
use Nette\IOException;
use Nette\Object;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;

class DatabaseBackup extends Object
{
    /** @var array */
    private $backupHandlers = [];

    /** @var array */
    private $databaseCredentials = [];

    /** @var string */
    private $backupTempPath;

    /** @var \MySQLDump */
    private $mysqlDump;

    /** @var Logger */
    private $logger;


    public function __construct(
        array $databaseCredentials,
        $backupTempPath,
        Logger $logger
    ) {
        $this->databaseCredentials = $databaseCredentials;
        $this->backupTempPath = $backupTempPath;
        $this->logger = $logger->channel('databaseBackup');

        $this->mysqlDump = new \MySQLDump(
            new \mysqli(
                $this->databaseCredentials['host'],
                $this->databaseCredentials['username'],
                $this->databaseCredentials['password'],
                $this->databaseCredentials['dbname']
            )
        );
    }


    public function addHandler(IDatabaseBackupHandler $backupHandler)
    {
        $this->backupHandlers[] = $backupHandler;
    }


    /**
     * @param string $fileNamePrefix
     * @param bool $removeBackupFileAtTheEnd
     * @throws \Exception
     * @throws IOException
     * @return ResultObject[]
     */
    public function backup($fileNamePrefix = null, $removeBackupFileAtTheEnd = false)
    {
        $storagePath = $this->prepareStoragePath($this->backupTempPath);

        $file = new DatabaseBackupFile($storagePath);
        if (!empty($fileNamePrefix)) {
            $file->setNamePrefix($fileNamePrefix);
        }

        $this->mysqlDump->save($file->getFilePath());

        $resultObjects = [];
        /** @var IDatabaseBackupHandler $handler */
        foreach ($this->backupHandlers as $handler) {
            $results = $handler->process($file);
            $resultObjects = array_merge($resultObjects, $results);
        }

        if ($removeBackupFileAtTheEnd === true) {
            $this->removeBackupFile($file);
        }

        return Arrays::flatten($resultObjects);
    }


    /**
     * @param $path
     * @return string
     * @throws IOException
     */
    private function prepareStoragePath($path)
    {
        if (!file_exists($path) and is_dir($path)) {
            try {
                FileSystem::createDir($path);
            } catch (IOException $e) {
                $this->logger->addCritical(sprintf('DIR creation failure: %s', $e));

                throw $e;
            }
        }

        return $path;
    }


    private function removeBackupFile(DatabaseBackupFile $file)
    {
        if (file_exists($file->getFilePath()) and !is_dir($file->getFilePath())) {
            FileSystem::delete($file->getFilePath());
        }
    }

}