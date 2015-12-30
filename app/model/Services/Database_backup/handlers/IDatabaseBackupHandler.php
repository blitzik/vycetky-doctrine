<?php

/**
 * Created by PhpStorm.
 * Author: Aleš Tichava
 * Date: 27.12.2015
 */

namespace App\Model\Database\Backup\Handlers;

use App\Model\Database\Backup\DatabaseBackupFile;
use App\Model\Subscribers\Results\ResultObject;

interface IDatabaseBackupHandler
{
    /**
     * @param DatabaseBackupFile $file
     * @return ResultObject
     */
    public function process(DatabaseBackupFile $file);
}