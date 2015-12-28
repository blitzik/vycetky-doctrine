<?php

namespace App\Model\Components;

use App\Model\Database\Backup\Handlers\IDatabaseBackupHandler;
use App\Model\Database\Backup\DatabaseBackup;
use Nette\Application\UI\Control;
use Kdyby\Monolog\Logger;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

class DatabaseBackupControl extends Control
{
    /** @var DatabaseBackup */
    private $databaseBackup;

    /** @var Logger */
    private $logger;

    /** @var Cache */
    private $cache;

    /** @var string */
    private $backupPassword;


    public function __construct(
        DatabaseBackup $databaseBackup,
        IStorage $storage,
        Logger $logger
    ) {
        $this->databaseBackup = $databaseBackup;
        $this->cache = new Cache($storage, 'databaseBackup');

        $this->logger = $logger->channel('databaseBackup');
    }


    /**
     * @param $password
     */
    public function setPasswordForBackup($password)
    {
        $this->backupPassword = $password;
    }


    public function addBackupHandler(IDatabaseBackupHandler $backupHandler)
    {
        $this->databaseBackup->addHandler($backupHandler);
    }


    public function handleBackup($pass)
    {
        if ($this->backupPassword !== null) {
            if ($this->backupPassword != $pass) {
                $this->logger->addWarning('Unauthorized try to backup database (auto)');
                return;
            }
        }

        if ($this->cache->load('databaseBackup') !== null) {
            $this->logger->addNotice('Another try to backup database (auto)');
            return;
        }

        try {
            $this->databaseBackup->backup('auto', true);
            $this->cache->save('databaseBackup', 'done', [Cache::EXPIRE => '23 hours']);

        } catch (\Exception $e) {
            $this->logger->addError(sprintf('Database backup failure (auto). %s', $e));
        }
    }

}