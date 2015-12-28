<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 27.12.2015
 */

namespace App\Model\Database\Backup\Handlers;

use App\Model\Database\Backup\DatabaseBackupFile;
use Kdyby\Monolog\Logger;
use Nette\Object;

class DatabaseBackupFileHandler extends Object implements IDatabaseBackupHandler
{
    /** @var array */
    private $uploadsCredentials;

    /** @var Logger */
    private $logger;


    public function __construct(
        array $uploadsCredentials,
        Logger $logger
    ) {
        $this->uploadsCredentials = $uploadsCredentials;
        $this->logger = $logger->channel('backupFileHandler');
    }


    public function process(DatabaseBackupFile $file)
    {
        $d = $file->getBackupDate();
        foreach ($this->uploadsCredentials as $credentials) {
            $backupPath = $credentials['path'] . '/' . $d->format('Y') . '/' . $d->format('F');
            $entireFilePath = $backupPath . '/' . $file->getFileName();

            try {
                $ftp = new \Ftp();
                $ftp->connect($credentials['host']);
                $ftp->login($credentials['username'], $credentials['password']);

                if (!$ftp->fileExists($backupPath)) {
                    $ftp->mkDirRecursive($backupPath);
                }

                $ftp->put($entireFilePath, $file->getFilePath(), FTP_BINARY);
                $ftp->close();

            } catch (\FtpException $e) {
                $this->logger->addCritical(sprintf('Uploading backup file\'s failed. %s', $e));
            }
        }
    }

}